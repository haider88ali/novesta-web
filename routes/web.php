<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\User\OauthController;
use App\Http\Controllers\User\LoginLinkController;

Route::get('/', [WelcomeController::class, 'home'])->name('home');

// Replace Jetstream's plain legal pages with Novesta's (filled from config/nova.php)
Route::get('/terms-of-service', [LegalController::class, 'terms'])->name('terms.show');
Route::get('/privacy-policy', [LegalController::class, 'privacy'])->name('policy.show');
Route::get('/refund-policy', [LegalController::class, 'refunds'])->name('refunds.show');

Route::prefix('auth')->group(
    function () {
        // OAuth
        Route::get('/redirect/{provider}', [OauthController::class, 'redirect'])->name('oauth.redirect');
        Route::get('/callback/{provider}', [OauthController::class, 'callback'])->name('oauth.callback');
        // Magic Link
        Route::middleware('throttle:login-link')->group(function () {
            Route::post('/login-link', [LoginLinkController::class, 'store'])->name('login-link.store');
            Route::get('/login-link/{token}', [LoginLinkController::class, 'login'])
                ->name('login-link.login')
                ->middleware('signed');
        });
    }
);

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    // Account: plan, usage, devices, downloads
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::delete('/auth/destroy/{provider}', [OauthController::class, 'destroy'])->name('oauth.destroy');

    // Desktop app sign-in (the app opens /device?code=XXXX-XXXX)
    Route::get('/device', [DeviceController::class, 'show'])->name('device.show');
    Route::post('/device', [DeviceController::class, 'approve'])->middleware('throttle:20,1')->name('device.approve');
    Route::delete('/devices/{token}', [DeviceController::class, 'destroy'])->name('devices.destroy');

    // Billing (Stripe)
    Route::get('/billing', [BillingController::class, 'index'])->name('billing');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::post('/billing/swap', [BillingController::class, 'swap'])->name('billing.swap');
    Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::post('/billing/resume', [BillingController::class, 'resume'])->name('billing.resume');
    Route::get('/billing/payment-method', [BillingController::class, 'paymentMethod'])->name('billing.payment-method');
    Route::get('/billing/invoices/{transaction}', [BillingController::class, 'invoice'])->name('billing.invoice');
});
