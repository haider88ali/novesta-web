<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UsageEvent;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Database\Eloquent\Builder;

/**
 * Which plan a user is on, how much they have used this month, and whether a request fits their limits.
 */
final class Quota
{
    /** Plan key: an active Stripe subscription wins, then the free trial, then "free". */
    public static function planKey(User $user): string
    {
        foreach (self::plans() as $key => $plan) {
            if ($plan['stripe_price_id'] && $user->subscribed('default', $plan['stripe_price_id'])) {
                return $key;
            }
        }

        return self::onTrial($user) ? Config::string('nova.trial_plan') : 'free';
    }

    public static function onTrial(User $user): bool
    {
        return ! self::hasPaidPlan($user) && $user->created_at?->gt(now()->subDays(Config::integer('nova.trial_days')));
    }

    public static function trialEndsAt(User $user): ?string
    {
        return self::onTrial($user) ? $user->created_at?->addDays(Config::integer('nova.trial_days'))->toIso8601String() : null;
    }

    public static function hasPaidPlan(User $user): bool
    {
        return $user->subscribed('default');
    }

    /** @return array{messages:int, searches:int, designs:int, voice_minutes:int, devices:int} */
    public static function limits(User $user): array
    {
        return self::plans()[self::planKey($user)]['limits'];
    }

    /** @return array{messages:int, searches:int, designs:int, voice_minutes:float, devices:int, cost_usd:float} */
    public static function usage(User $user): array
    {
        $events = UsageEvent::query()->where('user_id', $user->id)->where('created_at', '>=', now()->startOfMonth());

        // A user question can take several model calls (tool use); count each question once
        $messages = (clone $events)->where('kind', 'message')->whereNotNull('turn_id')->distinct()->count('turn_id')
            + (clone $events)->where('kind', 'message')->whereNull('turn_id')->count();

        return [
            'messages' => $messages,
            'searches' => (int) (clone $events)->sum('searches'),
            'designs' => (clone $events)->where('kind', 'design')->count(),
            'voice_minutes' => round((float) (clone $events)->sum('voice_minutes'), 1),
            'devices' => self::deviceTokens($user)->count(),
            'cost_usd' => round((float) (clone $events)->sum('cost_usd'), 4),
        ];
    }

    /**
     * Null when the request is allowed, otherwise a message for the user.
     * A turn that already started is always allowed to finish its tool calls.
     */
    public static function deny(User $user, string $kind, ?string $turnId = null): ?string
    {
        $limits = self::limits($user);
        $usage = self::usage($user);

        if ($kind === 'message' && $turnId
            && UsageEvent::query()->where('user_id', $user->id)->where('turn_id', $turnId)->exists()) {
            return null;
        }

        $key = ['message' => 'messages', 'design' => 'designs', 'voice' => 'voice_minutes'][$kind] ?? 'messages';
        if ($usage[$key] < $limits[$key]) {
            return null;
        }

        $label = ['messages' => 'messages', 'designs' => 'UI designs', 'voice_minutes' => 'voice minutes'][$key];

        return "You've used all {$limits[$key]} {$label} in your ".self::plans()[self::planKey($user)]['name']
            .' plan this month. Upgrade at '.route('billing').' to keep going.';
    }

    public static function searchesLeft(User $user): int
    {
        return max(0, self::limits($user)['searches'] - self::usage($user)['searches']);
    }

    /** @return Builder<PersonalAccessToken> */
    public static function deviceTokens(User $user)
    {
        return $user->tokens()->getQuery()->where('abilities', 'like', '%nova:device%');
    }

    /** @return array<string, array<string, mixed>> */
    public static function plans(): array
    {
        /** @var array<string, array<string, mixed>> */
        return Config::array('nova.plans');
    }

    /** Estimated USD cost of one gateway call. */
    public static function cost(?string $model, int $input, int $output, int $searches = 0, float $voiceMinutes = 0): float
    {
        $p = Config::array('nova.pricing');
        $m = $p[$model] ?? ['input' => 0, 'output' => 0];

        return ($input * $m['input'] + $output * $m['output']) / 1_000_000
            + $searches * $p['web_search_call']
            + $voiceMinutes * $p['tts_minute'];
    }
}
