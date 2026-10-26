<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\Quota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Account overview: plan, this month's usage, signed-in devices and app downloads.
 */
final class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $plan = Quota::planKey($user);

        return Inertia::render('Dashboard', [
            'plan' => ['key' => $plan, 'name' => Quota::plans()[$plan]['name'], 'price' => Quota::plans()[$plan]['price']],
            'trialEndsAt' => Quota::trialEndsAt($user),
            'limits' => Quota::limits($user),
            'usage' => Quota::usage($user),
            'devices' => Quota::deviceTokens($user)->latest()->get()->map(fn (PersonalAccessToken $t): array => [
                'id' => $t->id,
                'name' => $t->name,
                'last_used_at' => $t->last_used_at?->toIso8601String(),
                'created_at' => $t->created_at?->toIso8601String(),
            ]),
            'downloads' => Config::array('nova.downloads'),
        ]);
    }
}
