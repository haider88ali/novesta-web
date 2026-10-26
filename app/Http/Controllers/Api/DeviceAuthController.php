<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\DeviceCode;
use App\Services\Quota;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

/**
 * Desktop login (OAuth 2.0 device-authorization style):
 * 1. the app calls start() and shows the user_code,
 * 2. the user approves it at /device on the website,
 * 3. the app polls token() with its secret device_code and receives an API token once.
 */
final class DeviceAuthController extends Controller
{
    private const LIFETIME_MINUTES = 10;

    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';   // no 0/O/1/I

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_name' => ['required', 'string', 'max:80'],
            'platform' => ['required', 'in:mac,windows'],
        ]);

        $deviceCode = Str::random(48);
        DeviceCode::query()->create([
            'device_code_hash' => hash('sha256', $deviceCode),
            'user_code' => $userCode = $this->newUserCode(),
            'device_name' => strip_tags($data['device_name']),
            'platform' => $data['platform'],
            'expires_at' => now()->addMinutes(self::LIFETIME_MINUTES),
        ]);

        return response()->json([
            'device_code' => $deviceCode,
            'user_code' => $userCode,
            'verification_uri' => route('device.show'),
            'verification_uri_complete' => route('device.show', ['code' => $userCode]),
            'expires_in' => self::LIFETIME_MINUTES * 60,
            'interval' => 3,
        ]);
    }

    public function token(Request $request): JsonResponse
    {
        $data = $request->validate(['device_code' => ['required', 'string', 'max:100']]);

        $code = DeviceCode::query()->where('device_code_hash', hash('sha256', $data['device_code']))->first();

        if (! $code || $code->isExpired()) {
            return response()->json(['error' => 'expired_token'], 400);
        }
        if ($code->consumed_at) {
            return response()->json(['error' => 'invalid_grant'], 400);
        }
        if (! $code->approved_at || ! $code->user) {
            return response()->json(['error' => 'authorization_pending'], 400);
        }

        $user = $code->user;
        $platform = $code->platform === 'mac' ? 'Mac' : 'Windows';
        $token = $user->createToken("{$code->device_name} ({$platform})", ['nova:device'])->plainTextToken;
        $code->update(['consumed_at' => now()]);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => ['name' => $user->name, 'email' => $user->email],
            'plan' => Quota::planKey($user),
        ]);
    }

    private function newUserCode(): string
    {
        do {
            $raw = '';
            for ($i = 0; $i < 8; $i++) {
                $raw .= self::ALPHABET[random_int(0, mb_strlen(self::ALPHABET) - 1)];
            }
            $code = mb_substr($raw, 0, 4).'-'.mb_substr($raw, 4);
        } while (DeviceCode::query()->where('user_code', $code)->exists());

        return $code;
    }
}
