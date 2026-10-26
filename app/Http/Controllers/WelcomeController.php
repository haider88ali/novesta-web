<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Services\Quota;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;

final class WelcomeController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            // Same plans as checkout (config/nova.php), so the landing page never shows a stale price
            'plans' => collect(Quota::plans())->map(fn (array $plan, string $key): array => [
                'key' => $key,
                'name' => $plan['name'],
                'price' => $plan['price'],
                'description' => $plan['description'],
                'features' => $plan['features'],
            ])->values(),
            'trialDays' => Config::integer('nova.trial_days'),
            'downloads' => Config::array('nova.downloads'),
            'seo' => [
                'title' => 'Voice AI assistant for Mac & Windows',
            ],
        ]);
    }
}
