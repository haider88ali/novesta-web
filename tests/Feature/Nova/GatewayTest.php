<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UsageEvent;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    Config::set('nova.openai.key', 'sk-test');
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('Test (Mac)', ['nova:device'])->plainTextToken;
});

function fakeOpenAI(array $output = [], string $id = 'resp_1'): void
{
    $response = ['id' => $id, 'model' => 'gpt-4o-mini', 'output' => $output, 'usage' => ['input_tokens' => 1000, 'output_tokens' => 200]];
    Http::fake([
        'api.openai.com/v1/responses' => fn (Request $request) => $request['stream'] ?? false
            ? Http::response(
                "event: response.created\ndata: ".json_encode(['type' => 'response.created', 'response' => ['id' => $id]])."\n\n"
                ."event: response.output_text.delta\ndata: ".json_encode(['type' => 'response.output_text.delta', 'delta' => 'Hello'])."\n\n"
                ."event: response.completed\ndata: ".json_encode(['type' => 'response.completed', 'response' => $response])."\n\n",
                200, ['Content-Type' => 'text/event-stream'])
            : Http::response($response),
        'api.openai.com/v1/audio/speech' => Http::response('MP3DATA', 200, ['Content-Type' => 'audio/mpeg']),
    ]);
}

test('only device tokens can use the gateway', function (): void {
    $this->postJson(route('api.responses'), ['model' => 'gpt-4o-mini', 'input' => 'hi'])->assertUnauthorized();

    $plain = $this->user->createToken('api', ['read'])->plainTextToken;
    $this->withToken($plain)->postJson(route('api.responses'), ['model' => 'gpt-4o-mini', 'input' => 'hi'])->assertForbidden();
});

test('a request is forwarded with the server key, filtered, and its usage recorded', function (): void {
    fakeOpenAI([['type' => 'web_search_call'], ['type' => 'message']]);

    $this->withToken($this->token)->withHeaders(['X-Nova-Turn' => 'turn-1'])->postJson(route('api.responses'), [
        'model' => 'gpt-4o-mini',
        'input' => 'hi',
        'tools' => [['type' => 'web_search'], ['type' => 'function', 'name' => 'open_app'], ['type' => 'image_generation']],
        'max_output_tokens' => 999999,
        'metadata' => ['x' => 1],
    ])->assertOk()->assertJsonPath('id', 'resp_1');

    Http::assertSent(function (Request $request): bool {
        return $request->hasHeader('Authorization', 'Bearer sk-test')
            && collect($request['tools'])->pluck('type')->all() === ['web_search', 'function']
            && $request['max_output_tokens'] === 16000
            && ! isset($request['metadata']);
    });

    $event = UsageEvent::query()->sole();
    expect($event->kind)->toBe('message')
        ->and($event->turn_id)->toBe('turn-1')
        ->and($event->response_id)->toBe('resp_1')
        ->and($event->searches)->toBe(1)
        ->and($event->input_tokens)->toBe(1000)
        ->and($event->cost_usd)->toBeGreaterThan(0.01);
});

test('streamed responses are passed through and recorded', function (): void {
    fakeOpenAI();

    $response = $this->withToken($this->token)->postJson(route('api.responses'), ['model' => 'gpt-4o-mini', 'input' => 'hi', 'stream' => true]);

    expect($response->streamedContent())->toContain('response.output_text.delta')->toContain('Hello')
        ->and(UsageEvent::query()->sole()->output_tokens)->toBe(200);
});

test('models outside the allow list are rejected', function (): void {
    Http::fake();
    $this->withToken($this->token)->postJson(route('api.responses'), ['model' => 'o3-pro', 'input' => 'hi'])
        ->assertStatus(400)->assertJsonPath('error.code', 'invalid_model');
    Http::assertNothingSent();
});

test('another user\'s conversation cannot be continued', function (): void {
    UsageEvent::query()->create(['user_id' => User::factory()->create()->id, 'kind' => 'message', 'response_id' => 'resp_theirs']);
    Http::fake();

    $this->withToken($this->token)->postJson(route('api.responses'), ['model' => 'gpt-4o-mini', 'input' => 'hi', 'previous_response_id' => 'resp_theirs'])
        ->assertStatus(400)->assertJsonPath('error.code', 'invalid_previous_response');
});

test('the monthly message quota blocks new questions but lets a started turn finish', function (): void {
    Config::set('nova.trial_days', 0);   // free plan
    Config::set('nova.plans.free.limits.messages', 2);
    fakeOpenAI();
    UsageEvent::query()->create(['user_id' => $this->user->id, 'kind' => 'message', 'turn_id' => 'a']);
    UsageEvent::query()->create(['user_id' => $this->user->id, 'kind' => 'message', 'turn_id' => 'a']);   // same question, 2 calls
    UsageEvent::query()->create(['user_id' => $this->user->id, 'kind' => 'message', 'turn_id' => 'b']);

    $this->withToken($this->token)->withHeaders(['X-Nova-Turn' => 'b'])
        ->postJson(route('api.responses'), ['model' => 'gpt-4o-mini', 'input' => 'tool result'])->assertOk();
    $this->withToken($this->token)->withHeaders(['X-Nova-Turn' => 'c'])
        ->postJson(route('api.responses'), ['model' => 'gpt-4o-mini', 'input' => 'new question'])
        ->assertStatus(429)->assertJsonPath('error.code', 'nova_quota');
});

test('web search is removed once the search quota is used up', function (): void {
    Config::set('nova.trial_days', 0);
    Config::set('nova.plans.free.limits.searches', 1);
    UsageEvent::query()->create(['user_id' => $this->user->id, 'kind' => 'message', 'searches' => 1]);
    fakeOpenAI();

    $this->withToken($this->token)->postJson(route('api.responses'), [
        'model' => 'gpt-4o-mini', 'input' => 'news?', 'tools' => [['type' => 'web_search'], ['type' => 'function', 'name' => 'x']],
    ])->assertOk();

    Http::assertSent(fn (Request $r) => collect($r['tools'])->pluck('type')->all() === ['function']);
});

test('design requests count against the design quota', function (): void {
    Config::set('nova.trial_days', 0);
    Config::set('nova.plans.free.limits.designs', 1);
    fakeOpenAI();

    $send = fn () => $this->withToken($this->token)->withHeaders(['X-Nova-Kind' => 'design'])
        ->postJson(route('api.responses'), ['model' => 'gpt-4o-mini', 'input' => 'login screen']);

    $send()->assertOk();
    $send()->assertStatus(429);
    expect(UsageEvent::query()->where('kind', 'design')->count())->toBe(1);
});

test('text to speech is proxied and counted in voice minutes', function (): void {
    fakeOpenAI();

    $response = $this->withToken($this->token)->postJson(route('api.speech'), [
        'model' => 'gpt-4o-mini-tts', 'voice' => 'nova', 'input' => str_repeat('a', 900),
    ]);

    expect($response->streamedContent())->toBe('MP3DATA')
        ->and(UsageEvent::query()->sole()->voice_minutes)->toBe(1.0);
    $this->withToken($this->token)->postJson(route('api.speech'), ['model' => 'tts-1-hd', 'voice' => 'nova', 'input' => 'x'])
        ->assertUnprocessable();
});

test('the me endpoint reports plan, limits and usage', function (): void {
    $this->withToken($this->token)->getJson(route('api.me'))
        ->assertOk()
        ->assertJsonPath('plan.key', 'pro')
        ->assertJsonPath('limits.messages', 2000)
        ->assertJsonPath('usage.devices', 1)
        ->assertJsonMissingPath('usage.cost_usd');
});

test('speech to text is forwarded and logged, and needs messages left', function (): void {
    Http::fake(['api.openai.com/v1/audio/transcriptions' => Http::response(['text' => 'open slack'])]);
    $audio = \Illuminate\Http\UploadedFile::fake()->create('speech.webm', 40, 'audio/webm');

    $this->withToken($this->token)->post(route('api.transcribe'), ['file' => $audio, 'model' => 'gpt-4o-mini-transcribe', 'language' => 'en', 'duration' => 30], ['Accept' => 'application/json'])
        ->assertOk()->assertJson(['text' => 'open slack']);

    Http::assertSent(fn (Request $r) => $r->url() === 'https://api.openai.com/v1/audio/transcriptions' && $r->isMultipart());
    $event = UsageEvent::query()->sole();
    expect($event->kind)->toBe('transcribe')->and($event->cost_usd)->toBe(0.0015);

    // Other models are rejected; an exhausted message quota blocks transcription too
    $this->withToken($this->token)->post(route('api.transcribe'), ['file' => $audio, 'model' => 'whisper-1'], ['Accept' => 'application/json'])
        ->assertUnprocessable();
    Config::set('nova.trial_days', 0);
    Config::set('nova.plans.free.limits.messages', 0);
    $this->withToken($this->token)->post(route('api.transcribe'), ['file' => $audio, 'model' => 'gpt-4o-mini-transcribe'], ['Accept' => 'application/json'])
        ->assertStatus(429);
});
