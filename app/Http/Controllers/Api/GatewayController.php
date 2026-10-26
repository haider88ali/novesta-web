<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Services\Quota;
use App\Models\UsageEvent;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\PersonalAccessToken;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * OpenAI-compatible gateway for the Nova desktop app.
 *
 * The app points the OpenAI SDK at /api/v1 with its device token as the API key. Every request is
 * checked against the user's plan, stripped down to allowed models/tools, sent to OpenAI with the
 * server's key, streamed back, and logged with its token counts and estimated cost.
 */
final class GatewayController extends Controller
{
    /** Request fields passed to OpenAI; anything else is dropped. */
    private const RESPONSES_FIELDS = [
        'model', 'instructions', 'input', 'tools', 'tool_choice', 'parallel_tool_calls', 'previous_response_id',
        'stream', 'text', 'reasoning', 'max_output_tokens', 'temperature', 'top_p',
    ];

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $plan = Quota::planKey($user);

        return response()->json([
            'user' => ['name' => $user->name, 'email' => $user->email],
            'plan' => ['key' => $plan, 'name' => Quota::plans()[$plan]['name']],
            'trial_ends_at' => Quota::trialEndsAt($user),
            'limits' => Quota::limits($user),
            'usage' => Arr::except(Quota::usage($user), 'cost_usd'),
            'billing_url' => route('billing'),
        ]);
    }

    public function responses(Request $request): JsonResponse|StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $key = Config::get('nova.openai.key')) {
            return $this->error('Nova AI is not configured on the server yet.', 503, 'not_configured');
        }

        $kind = $request->header('X-Nova-Kind') === 'design' ? 'design' : 'message';
        $turn = mb_substr((string) preg_replace('/[^A-Za-z0-9-]/', '', (string) $request->header('X-Nova-Turn')), 0, 64) ?: null;
        $body = Arr::only((array) $request->json()->all(), self::RESPONSES_FIELDS);

        if (! in_array($body['model'] ?? null, Config::array('nova.openai.chat_models'), true)) {
            return $this->error('This model is not available in Nova.', 400, 'invalid_model');
        }
        // Only continue conversations this user started
        if (isset($body['previous_response_id']) && ! UsageEvent::query()
            ->where('user_id', $user->id)->where('response_id', (string) $body['previous_response_id'])->exists()) {
            return $this->error('Unknown conversation.', 400, 'invalid_previous_response');
        }
        if ($message = Quota::deny($user, $kind, $turn)) {
            return $this->error($message, 429, 'nova_quota');
        }

        // Allowed tool types only; web search is dropped once the plan's searches are used up
        $searchesLeft = Quota::searchesLeft($user);
        $tools = array_values(array_filter((array) ($body['tools'] ?? []), fn ($tool): bool => is_array($tool)
            && in_array($tool['type'] ?? null, Config::array('nova.openai.allowed_tools'), true)
            && (($tool['type'] ?? null) !== 'web_search' || $searchesLeft > 0)));
        $tools ? $body['tools'] = $tools : Arr::forget($body, ['tools', 'tool_choice', 'parallel_tool_calls']);

        $cap = Config::integer('nova.openai.max_output_tokens');
        $body['max_output_tokens'] = min((int) ($body['max_output_tokens'] ?? $cap), $cap);
        $body['store'] = true;   // needed for previous_response_id

        $upstream = Http::withToken($key)->withOptions(['stream' => true])->timeout(300)
            ->post(Config::string('nova.openai.base_url').'/responses', $body);

        if ($upstream->failed()) {
            return response()->json($upstream->json() ?? ['error' => ['message' => 'The AI service failed.']], $upstream->status());
        }

        $record = fn (array $response) => $this->record($user, $request, $kind, $turn, (string) $body['model'], $response);

        if (empty($body['stream'])) {
            $json = (array) $upstream->json();
            $record($json);

            return response()->json($json);
        }

        $stream = $upstream->toPsrResponse()->getBody();

        return response()->stream(function () use ($stream, $record): void {
            $final = null;
            $this->pipe($stream, function (string $block) use (&$final): void {
                if (preg_match('/^data: (.+)$/m', $block, $m)) {
                    $event = json_decode($m[1], true);
                    if (in_array($event['type'] ?? null, ['response.completed', 'response.incomplete', 'response.failed'], true)) {
                        $final = $event['response'] ?? null;
                    }
                }
            });
            if (is_array($final)) {
                $record($final);
            }
        }, 200, ['Content-Type' => 'text/event-stream', 'Cache-Control' => 'no-cache', 'X-Accel-Buffering' => 'no']);
    }

    public function speech(Request $request): JsonResponse|StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $key = Config::get('nova.openai.key')) {
            return $this->error('Nova AI is not configured on the server yet.', 503, 'not_configured');
        }

        $data = $request->validate([
            'model' => ['required', Rule::in(Config::array('nova.openai.tts_models'))],
            'voice' => ['required', Rule::in(Config::array('nova.openai.tts_voices'))],
            'input' => ['required', 'string', 'max:1500'],
            'instructions' => ['nullable', 'string', 'max:500'],
        ]);
        if ($message = Quota::deny($user, 'voice')) {
            return $this->error($message, 429, 'nova_quota');
        }

        $upstream = Http::withToken($key)->withOptions(['stream' => true])->timeout(120)
            ->post(Config::string('nova.openai.base_url').'/audio/speech', $data + ['response_format' => 'mp3']);
        if ($upstream->failed()) {
            return response()->json($upstream->json() ?? ['error' => ['message' => 'The voice service failed.']], $upstream->status());
        }

        // ~900 characters of speech per minute
        $minutes = max(0.05, round(mb_strlen($data['input']) / 900, 3));
        $this->record($user, $request, 'voice', null, $data['model'], [], $minutes);

        $stream = $upstream->toPsrResponse()->getBody();

        return response()->stream(fn () => $this->pipe($stream), 200, ['Content-Type' => 'audio/mpeg', 'Cache-Control' => 'no-cache']);
    }

    /**
     * Speech to text for the desktop app's microphone. Each recording becomes a message,
     * so it is allowed while the user still has messages left this month.
     */
    public function transcribe(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $key = Config::get('nova.openai.key')) {
            return $this->error('Nova AI is not configured on the server yet.', 503, 'not_configured');
        }

        $data = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimetypes:audio/webm,video/webm,audio/ogg,audio/mp4,audio/mpeg,audio/wav,audio/x-wav'],
            'model' => ['required', Rule::in(Config::array('nova.openai.stt_models'))],
            'language' => ['nullable', 'in:en,ur,hi'],
            'prompt' => ['nullable', 'string', 'max:300'],
            'duration' => ['nullable', 'numeric', 'min:0', 'max:600'],   // seconds, reported by the app for cost logging
        ]);
        if ($message = Quota::deny($user, 'message', $request->header('X-Nova-Turn'))) {
            return $this->error($message, 429, 'nova_quota');
        }

        $file = $request->file('file');
        $upstream = Http::withToken($key)->timeout(60)
            ->attach('file', fopen($file->getRealPath(), 'r'), 'speech.'.($file->guessExtension() ?: 'webm'))
            ->post(Config::string('nova.openai.base_url').'/audio/transcriptions', array_filter([
                'model' => $data['model'],
                'language' => $data['language'] ?? null,
                'prompt' => $data['prompt'] ?? null,
            ]));

        if ($upstream->failed()) {
            return response()->json($upstream->json() ?? ['error' => ['message' => 'The speech service failed.']], $upstream->status());
        }

        $minutes = round(((float) ($data['duration'] ?? 0)) / 60, 3);
        UsageEvent::query()->create([
            'user_id' => $user->id,
            'token_id' => ($token = $user->currentAccessToken()) instanceof PersonalAccessToken ? $token->id : null,
            'kind' => 'transcribe',
            'model' => $data['model'],
            'cost_usd' => $minutes * Config::array('nova.pricing')['stt_minute'],
        ]);

        return response()->json(['text' => (string) $upstream->json('text', '')]);
    }

    /** Copy an upstream body to the client as it arrives, optionally handing each SSE block to $onBlock. */
    private function pipe(StreamInterface $stream, ?callable $onBlock = null): void
    {
        $buffer = '';
        while (! $stream->eof()) {
            $chunk = $stream->read(8192);
            if ($chunk === '') {
                continue;
            }
            echo $chunk;
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            if ($onBlock) {
                // Byte-wise on purpose: a chunk can end in the middle of a multi-byte character
                $buffer .= $chunk;
                while (($pos = strpos($buffer, "\n\n")) !== false) {
                    $onBlock(substr($buffer, 0, $pos));
                    $buffer = substr($buffer, $pos + 2);
                }
            }
        }
    }

    /** @param array<string, mixed> $response */
    private function record(User $user, Request $request, string $kind, ?string $turn, string $model, array $response, float $voiceMinutes = 0): void
    {
        $usage = (array) ($response['usage'] ?? []);
        $searches = collect((array) ($response['output'] ?? []))->where('type', 'web_search_call')->count();
        $input = (int) ($usage['input_tokens'] ?? 0);
        $output = (int) ($usage['output_tokens'] ?? 0);
        $token = $user->currentAccessToken();

        UsageEvent::query()->create([
            'user_id' => $user->id,
            'token_id' => $token instanceof PersonalAccessToken ? $token->id : null,
            'kind' => $kind,
            'turn_id' => $turn,
            'model' => $model,
            'response_id' => $response['id'] ?? null,
            'input_tokens' => $input,
            'output_tokens' => $output,
            'searches' => $searches,
            'voice_minutes' => $voiceMinutes,
            'cost_usd' => Quota::cost($model, $input, $output, $searches, $voiceMinutes),
        ]);
    }

    private function error(string $message, int $status, string $code): JsonResponse
    {
        return response()->json(['error' => ['message' => $message, 'type' => $code, 'code' => $code]], $status);
    }
}
