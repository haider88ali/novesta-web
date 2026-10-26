<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\Quota;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Laravel\Paddle\Transaction;

/**
 * Plans and billing through Paddle (Cashier Paddle). Checkout runs in Paddle's overlay on this page.
 */
final class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $paddleReady = self::paddleReady();
        $subscription = $user->subscription('default');
        $active = $subscription?->valid() ?? false;

        $plans = collect(Quota::plans())->map(fn (array $plan, string $key): array => [
            'key' => $key,
            'name' => $plan['name'],
            'price' => $plan['price'],
            'description' => $plan['description'],
            'features' => $plan['features'],
            // Paddle.js overlay options for new subscribers
            'checkout' => $paddleReady && $plan['paddle_price_id'] && ! $active
                ? $user->subscribe($plan['paddle_price_id'], 'default')->returnTo(route('billing'))->options()
                : null,
            'can_switch' => $active && $plan['paddle_price_id'] && ! $subscription->hasPrice($plan['paddle_price_id']),
        ])->values();

        return Inertia::render('Billing/Index', [
            'currentPlan' => Quota::planKey($user),
            'trialEndsAt' => Quota::trialEndsAt($user),
            'paddleReady' => $paddleReady,
            'subscription' => $subscription ? [
                'status' => $subscription->status,
                'canceled' => $subscription->canceled(),
                'on_grace_period' => $subscription->onGracePeriod(),
                'ends_at' => $subscription->ends_at?->toIso8601String(),
                'past_due' => $subscription->pastDue(),
            ] : null,
            'plans' => $plans,
            'transactions' => Inertia::defer(fn () => $user->transactions()->latest('billed_at')->take(24)->get()
                ->map(fn (Transaction $t): array => [
                    'id' => $t->id,
                    'total' => $t->total(),
                    'status' => $t->status,
                    'billed_at' => $t->billed_at?->toIso8601String(),
                ])),
        ]);
    }

    /** Move an active subscriber to another paid plan (prorated by Paddle). */
    public function swap(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate(['plan' => ['required', Rule::in(array_keys(Quota::plans()))]]);
        $price = Quota::plans()[$data['plan']]['paddle_price_id'];
        abort_unless($price && $user->subscribed('default'), 422);

        $user->subscription('default')?->swap($price);

        return back()->with('flash', ['banner' => 'Your plan was changed.']);
    }

    /** Cancel at the end of the paid period. */
    public function cancel(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->subscription('default')?->cancel();

        return back();
    }

    public function resume(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $subscription = $user->subscription('default');
        if ($subscription?->onGracePeriod()) {
            $subscription->stopCancelation();
        }

        return back();
    }

    public function paymentMethod(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $subscription = $user->subscription('default');
        abort_unless($subscription, 404);

        return $subscription->redirectToUpdatePaymentMethod();
    }

    public function invoice(Request $request, string $transaction): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $user->transactions()->findOrFail($transaction)->redirectToInvoicePdf();
    }

    public static function paddleReady(): bool
    {
        return filled(Config::get('cashier.api_key')) && filled(Config::get('cashier.client_side_token'));
    }
}
