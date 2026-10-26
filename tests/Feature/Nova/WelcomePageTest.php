<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia as Assert;

test('the landing page shows the plans and prices from config', function (): void {
    Config::set('nova.plans.pro.price', 15);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->has('plans', 3)
            ->where('plans.1.key', 'pro')
            ->where('plans.1.price', 15)
            ->where('trialDays', 7)
            ->missing('plans.1.paddle_price_id')
            ->missing('plans.1.limits'));
});
