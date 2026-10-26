<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\DeviceCode;
use App\Services\Quota;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

/**
 * Website side of desktop login: confirm a code shown by Nova, and remove signed-in devices.
 */
final class DeviceController extends Controller
{
    public function show(Request $request): Response
    {
        $userCode = mb_strtoupper(trim((string) $request->query('code', '')));
        $pending = $userCode ? $this->pending($userCode) : null;

        return Inertia::render('Device/Approve', [
            'code' => $userCode,
            'device' => $pending ? ['name' => $pending->device_name, 'platform' => $pending->platform] : null,
            'approved' => (bool) $request->session()->get('device_approved'),
        ]);
    }

    public function approve(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate(['code' => ['required', 'string', 'max:9']]);
        $code = $this->pending(mb_strtoupper(trim($data['code'])));

        if (! $code) {
            return back()->withErrors(['code' => 'That code is invalid or has expired. Start the sign-in again in Nova.']);
        }

        $limits = Quota::limits($user);
        if (Quota::deviceTokens($user)->count() >= $limits['devices']) {
            return back()->withErrors(['code' => "Your plan allows {$limits['devices']} device(s). Remove one in your account, or upgrade."]);
        }

        $code->update(['user_id' => $user->id, 'approved_at' => now()]);

        return redirect()->route('device.show')->with('device_approved', true);
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        Quota::deviceTokens($user)->whereKey($token)->delete();

        return back();
    }

    private function pending(string $userCode): ?DeviceCode
    {
        return DeviceCode::query()
            ->where('user_code', $userCode)
            ->whereNull('approved_at')
            ->where('expires_at', '>', now())
            ->first();
    }
}
