<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\Quota;
use Illuminate\Http\Request;
use Laravel\Cashier\Invoice;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Plans and billing through Stripe (Laravel Cashier). New subscribers pay on Stripe Checkout;
 * card changes go through the Stripe customer portal. Stripe's webhook keeps the subscription rows current.
 */
final class BillingController extends Controller
{
    public static function stripeReady(): bool
    {
        return filled(Config::get('cashier.key')) && filled(Config::get('cashier.secret'));
    }

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $stripeReady = self::stripeReady();
        $subscription = $user->subscription('default');
        $active = $subscription?->valid() ?? false;

        $plans = collect(Quota::plans())->map(fn (array $plan, string $key): array => [
            'key' => $key,
            'name' => $plan['name'],
            'price' => $plan['price'],
            'description' => $plan['description'],
            'features' => $plan['features'],
            'can_subscribe' => $stripeReady && $plan['stripe_price_id'] && ! $active,
            'can_switch' => $active && $plan['stripe_price_id'] && ! $subscription->hasPrice($plan['stripe_price_id']),
        ])->values();

        return Inertia::render('Billing/Index', [
            'currentPlan' => Quota::planKey($user),
            'trialEndsAt' => Quota::trialEndsAt($user),
            'stripeReady' => $stripeReady,
            'subscription' => $subscription ? [
                'status' => $subscription->stripe_status,
                'canceled' => $subscription->canceled(),
                'on_grace_period' => $subscription->onGracePeriod(),
                'ends_at' => $subscription->ends_at?->toIso8601String(),
                'past_due' => $subscription->pastDue() || $subscription->hasIncompletePayment(),
            ] : null,
            'transactions' => Inertia::defer(fn () => $stripeReady && $user->hasStripeId()
                ? $user->invoices(false, ['limit' => 24])->map(fn (Invoice $invoice): array => [
                    'id' => $invoice->id,
                    'total' => $invoice->total(),
                    'status' => $invoice->status,
                    'billed_at' => $invoice->date()->toIso8601String(),
                ])->values()
                : []),
            'plans' => $plans,
        ]);
    }

    /** Send a new subscriber to Stripe Checkout for the chosen plan. */
    public function checkout(Request $request): HttpResponse
    {
        /** @var User $user */
        $user = $request->user();
        $price = self::priceFor($request);
        abort_unless(self::stripeReady() && $price && ! $user->subscribed('default'), 422);

        $builder = $user->newSubscription('default', $price)->allowPromotionCodes();
        $options = [
            'success_url' => route('billing').'?checkout=success',
            'cancel_url' => route('billing'),
        ];

        return Inertia::location($builder->checkout($options)->url);
    }

    /** Move an active subscriber to another paid plan (Stripe prorates the difference). */
    public function swap(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $price = self::priceFor($request);
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
            $subscription->resume();
        }

        return back();
    }

    /** Stripe's customer portal: update the card, see receipts, fix a failed payment. */
    public function paymentMethod(Request $request): HttpResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasStripeId(), 404);

        return Inertia::location($user->billingPortalUrl(route('billing')));
    }

    /** Stripe's hosted page for one of the user's invoices (findInvoice refuses other customers' invoices). */
    public function invoice(Request $request, string $transaction): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $invoice = $user->hasStripeId() ? $user->findInvoice($transaction) : null;
        abort_unless($invoice?->hosted_invoice_url, 404);

        return redirect()->away($invoice->hosted_invoice_url);
    }

    private static function priceFor(Request $request): ?string
    {
        $data = $request->validate(['plan' => ['required', Rule::in(array_keys(Quota::plans()))]]);

        return Quota::plans()[$data['plan']]['stripe_price_id'];
    }
}
