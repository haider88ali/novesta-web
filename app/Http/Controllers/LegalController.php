<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;

/**
 * Terms, Privacy and Refund pages. The text lives in resources/markdown; :placeholders are
 * filled from config('nova') so names, prices and the support address stay in one place.
 */
final class LegalController extends Controller
{
    public function terms(): Response
    {
        return $this->page('Terms of Service', 'terms.md');
    }

    public function privacy(): Response
    {
        return $this->page('Privacy Policy', 'policy.md');
    }

    public function refunds(): Response
    {
        return $this->page('Refund Policy', 'refunds.md');
    }

    private function page(string $title, string $file): Response
    {
        return Inertia::render('Legal', [
            'title' => $title,
            'content' => $this->render($file),
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    private function render(string $file): string
    {
        $legal = Config::array('nova.legal');
        $plans = Config::array('nova.plans');

        $markdown = strtr((string) file_get_contents(resource_path("markdown/{$file}")), [
            ':app' => (string) Config::get('app.name', 'Nova'),
            ':url' => (string) Config::get('app.url'),
            ':operator' => $legal['operator'],
            ':country' => $legal['country'],
            ':email' => $legal['support_email'],
            ':updated' => $legal['updated'],
            ':trial_days' => (string) Config::integer('nova.trial_days'),
            ':pro_price' => '$'.$plans['pro']['price'],
            ':business_price' => '$'.$plans['business']['price'],
        ]);

        return Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }
}
