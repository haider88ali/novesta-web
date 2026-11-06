<?php

declare(strict_types=1);

use App\Models\User;
use Tests\FakeStripe;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia as Assert;

test('the legal pages are Novesta\'s, filled from config', function (string $route, string $heading): void {
    Config::set('nova.legal.support_email', 'help@nova.test');
    Config::set('nova.legal.operator', 'Nova Labs');

    $this->get(route($route))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Legal')
            ->where('title', $heading)
            ->where('content', fn (string $html): bool => str_contains($html, $heading)
                && str_contains($html, 'help@nova.test')
                && ! str_contains($html, 'LaraSonic')
                && ! preg_match('/:(app|email|operator|country|updated|trial_days|pro_price)\b/', $html)));
})->with([
    ['terms.show', 'Terms of Service'],
    ['policy.show', 'Privacy Policy'],
    ['refunds.show', 'Refund Policy'],
]);

test('the support email is shared with every page', function (): void {
    Config::set('nova.legal.support_email', 'help@nova.test');

    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('supportEmail', 'help@nova.test'));
});

test('deleting an account cancels its Stripe subscription first', function (): void {
    Config::set('cashier.secret', 'sk_test_fake');
    $stripe = FakeStripe::fake(['DELETE /v1/subscriptions/sub_123' => [
        'id' => 'sub_123', 'object' => 'subscription', 'status' => 'canceled',
        'current_period_end' => now()->timestamp, 'items' => ['object' => 'list', 'data' => []],
    ]]);
    $this->actingAs($user = User::factory()->create(['stripe_id' => 'cus_123']));
    $user->subscriptions()->create([
        'type' => 'default', 'stripe_id' => 'sub_123', 'stripe_status' => 'active', 'stripe_price' => 'price_pro',
    ]);

    $this->delete('/user', ['password' => 'password']);

    expect($user->fresh())->toBeNull()
        ->and($stripe->sent('DELETE', '/v1/subscriptions/sub_123'))->not->toBeNull();
});
