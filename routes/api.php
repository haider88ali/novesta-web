<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiUserController;
use App\Http\Controllers\Api\GatewayController;
use App\Http\Controllers\Api\DeviceAuthController;

Route::apiResource('user', ApiUserController::class)->middleware('auth:sanctum');

// Desktop login (device code flow)
Route::middleware('throttle:device-auth')->prefix('device')->group(function (): void {
    Route::post('/code', [DeviceAuthController::class, 'start'])->name('api.device.code');
    Route::post('/token', [DeviceAuthController::class, 'token'])->name('api.device.token');
});

// AI gateway for the desktop app — OpenAI-compatible paths, device tokens only
Route::middleware(['auth:sanctum', 'abilities:nova:device', 'throttle:gateway'])->prefix('v1')->group(function (): void {
    Route::get('/me', [GatewayController::class, 'me'])->name('api.me');
    Route::post('/responses', [GatewayController::class, 'responses'])->name('api.responses');
    Route::post('/audio/speech', [GatewayController::class, 'speech'])->name('api.speech');
});
