<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\DeviceCode;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

function startDevice($test, string $name = 'Ali MacBook'): array
{
    return $test->postJson(route('api.device.code'), ['device_name' => $name, 'platform' => 'mac'])
        ->assertOk()
        ->assertJsonStructure(['device_code', 'user_code', 'verification_uri', 'verification_uri_complete', 'expires_in', 'interval'])
        ->json();
}

test('the full device login flow issues one device token', function (): void {
    $start = startDevice($this);
    expect($start['user_code'])->toMatch('/^[A-Z2-9]{4}-[A-Z2-9]{4}$/');

    // Before approval the app keeps polling
    $this->postJson(route('api.device.token'), ['device_code' => $start['device_code']])
        ->assertStatus(400)->assertJson(['error' => 'authorization_pending']);

    // The user sees the device and approves it
    $this->actingAs($this->user)->get(route('device.show', ['code' => $start['user_code']]))
        ->assertInertia(fn ($page) => $page->component('Device/Approve')->where('device.name', 'Ali MacBook'));
    $this->actingAs($this->user)->post(route('device.approve'), ['code' => mb_strtolower($start['user_code'])])
        ->assertRedirect(route('device.show'));

    $token = $this->postJson(route('api.device.token'), ['device_code' => $start['device_code']])
        ->assertOk()->assertJsonPath('user.email', $this->user->email)->json('access_token');

    expect($this->user->tokens()->count())->toBe(1)
        ->and($this->user->tokens()->first()->abilities)->toBe(['nova:device'])
        ->and($this->user->tokens()->first()->name)->toBe('Ali MacBook (Mac)');

    // The token works on the gateway, and the code cannot be used twice
    $this->withToken($token)->getJson(route('api.me'))->assertOk()->assertJsonPath('plan.key', 'pro');
    $this->postJson(route('api.device.token'), ['device_code' => $start['device_code']])
        ->assertStatus(400)->assertJson(['error' => 'invalid_grant']);
});

test('expired and unknown codes are rejected', function (): void {
    $start = startDevice($this);
    DeviceCode::query()->update(['expires_at' => now()->subMinute()]);

    $this->actingAs($this->user)->post(route('device.approve'), ['code' => $start['user_code']])
        ->assertSessionHasErrors('code');
    $this->postJson(route('api.device.token'), ['device_code' => $start['device_code']])
        ->assertStatus(400)->assertJson(['error' => 'expired_token']);
    $this->postJson(route('api.device.token'), ['device_code' => 'nope'])
        ->assertStatus(400)->assertJson(['error' => 'expired_token']);
});

test('the plan device limit is enforced', function (): void {
    Config::set('nova.trial_days', 0);   // free plan: 1 device
    $this->user->createToken('Old laptop (Mac)', ['nova:device']);

    $start = startDevice($this);
    $this->actingAs($this->user)->post(route('device.approve'), ['code' => $start['user_code']])
        ->assertSessionHasErrors('code');
});

test('a user can remove only their own devices', function (): void {
    $mine = $this->user->createToken('Mine (Mac)', ['nova:device'])->accessToken;
    $other = User::factory()->create()->createToken('Theirs (Mac)', ['nova:device'])->accessToken;

    $this->actingAs($this->user)->delete(route('devices.destroy', ['token' => $other->id]));
    $this->actingAs($this->user)->delete(route('devices.destroy', ['token' => $mine->id]));

    expect($this->user->tokens()->count())->toBe(0)
        ->and($other->fresh())->not->toBeNull();
});

test('approving a device requires login', function (): void {
    $this->get(route('device.show'))->assertRedirect(route('login'));
});

test('the app can sign itself out, revoking only its own token', function (): void {
    $token = $this->user->createToken('Laptop (Mac)', ['nova:device'])->plainTextToken;
    $this->user->createToken('Desktop (Windows)', ['nova:device']);

    $this->withToken($token)->deleteJson(route('api.device.signout'))->assertOk();

    expect($this->user->tokens()->pluck('name')->all())->toBe(['Desktop (Windows)']);
    app('auth')->forgetGuards();
    $this->withToken($token)->getJson(route('api.me'))->assertUnauthorized();
});
