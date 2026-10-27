<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia as Assert;

test('the legal pages are Nova\'s, filled from config', function (string $route, string $heading): void {
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

test('deleting an account cancels its Paddle subscription first', function (): void {
    Config::set('cashier.api_key', 'test_key');
    Http::fake(['*/subscriptions/sub_123/cancel' => Http::response(['data' => [
        'status' => 'canceled', 'canceled_at' => now()->toIso8601String(),
    ]])]);
    $this->actingAs($user = User::factory()->create());
    $user->subscriptions()->create([
        'type' => 'default', 'paddle_id' => 'sub_123', 'status' => 'active',
    ]);

    $this->delete('/user', ['password' => 'password']);

    expect($user->fresh())->toBeNull();
    Http::assertSent(fn ($request): bool => str_ends_with($request->url(), 'subscriptions/sub_123/cancel')
        && $request['effective_from'] === 'immediately');
});
