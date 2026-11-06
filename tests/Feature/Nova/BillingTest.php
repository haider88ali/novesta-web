<?php

declare(strict_types=1);

use App\Models\User;
use Tests\FakeStripe;
use App\Services\Quota;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    Config::set('nova.plans.pro.stripe_price_id', 'price_pro');
    Config::set('nova.plans.business.stripe_price_id', 'price_business');
});

function stripeKeys(): void
{
    Config::set('cashier.key', 'pk_test_fake');
    Config::set('cashier.secret', 'sk_test_fake');
}

test('without Stripe keys the billing page explains setup and offers no checkout', function (): void {
    Config::set('cashier.key');
    Config::set('cashier.secret');
    $this->actingAs(User::factory()->create());

    $this->get(route('billing'))->assertInertia(fn (Assert $page) => $page
        ->component('Billing/Index')
        ->where('stripeReady', false)
        ->where('plans.1.can_subscribe', false)
        ->missing('plans.1.stripe_price_id'));
});

test('subscribing sends the user to Stripe Checkout for that plan', function (): void {
    stripeKeys();
    $stripe = FakeStripe::fake([
        'POST /v1/customers' => ['id' => 'cus_new', 'object' => 'customer'],
        'POST /v1/checkout/sessions' => ['id' => 'cs_1', 'object' => 'checkout.session', 'url' => 'https://checkout.stripe.com/c/pay/cs_1'],
    ]);
    // fresh(): load every column, as a real request does
    $this->actingAs($user = User::factory()->create()->fresh());

    $this->get(route('billing'))->assertInertia(fn (Assert $page) => $page->where('plans.1.can_subscribe', true));

    $this->post(route('billing.checkout'), ['plan' => 'pro'], ['X-Inertia' => 'true'])
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', 'https://checkout.stripe.com/c/pay/cs_1');

    $session = $stripe->sent('POST', '/v1/checkout/sessions')['params'];
    expect($session['mode'])->toBe('subscription')
        ->and($session['customer'])->toBe('cus_new')
        ->and($session['line_items'][0]['price'])->toBe('price_pro')
        ->and($session['success_url'])->toContain('/billing?checkout=success')
        ->and($user->fresh()->stripe_id)->toBe('cus_new');
});

test('the free plan and unknown plans cannot be checked out', function (string $plan, int $status): void {
    stripeKeys();
    $this->actingAs(User::factory()->create());

    $this->post(route('billing.checkout'), ['plan' => $plan])->assertStatus($status);
})->with([
    ['free', 422],
    ['enterprise', 302], // validation error redirects back
]);

test('an active Stripe subscription sets the plan and blocks a second checkout', function (): void {
    stripeKeys();
    $this->actingAs($user = User::factory()->create(['stripe_id' => 'cus_1']));
    $user->subscriptions()->create([
        'type' => 'default', 'stripe_id' => 'sub_1', 'stripe_status' => 'active', 'stripe_price' => 'price_business',
    ]);

    expect(Quota::planKey($user))->toBe('business')
        ->and(Quota::onTrial($user))->toBeFalse();

    $this->get(route('billing'))->assertInertia(fn (Assert $page) => $page
        ->where('currentPlan', 'business')
        ->where('subscription.status', 'active')
        ->where('plans.1.can_subscribe', false)
        ->where('plans.1.can_switch', true)
        ->where('plans.2.can_switch', false));

    $this->post(route('billing.checkout'), ['plan' => 'pro'])->assertStatus(422);
});

test('a cancelled subscription keeps its plan until the period ends', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_1', 'created_at' => now()->subMonths(2)]);
    $user->subscriptions()->create([
        'type' => 'default', 'stripe_id' => 'sub_1', 'stripe_status' => 'active', 'stripe_price' => 'price_pro',
        'ends_at' => now()->addDays(5),
    ]);

    expect(Quota::planKey($user))->toBe('pro');

    $user->subscriptions()->update(['ends_at' => now()->subDay()]);
    expect(Quota::planKey($user->fresh()))->toBe('free');
});

test('users without a Stripe customer have no card portal or invoices', function (): void {
    $this->actingAs(User::factory()->create()->fresh());

    $this->get(route('billing.payment-method'))->assertNotFound();
    $this->get(route('billing.invoice', ['transaction' => 'in_123']))->assertNotFound();
});
