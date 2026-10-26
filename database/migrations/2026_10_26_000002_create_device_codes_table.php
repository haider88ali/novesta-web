<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Device login: the desktop app shows a short code, the user approves it on the website,
        // and the app exchanges its secret device code for an API token.
        Schema::create('device_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('device_code_hash', 64)->unique();   // sha256 of the secret the app keeps
            $table->string('user_code', 9)->unique();           // e.g. KQ7M-4XPD, typed or confirmed by the user
            $table->string('device_name', 80);
            $table->string('platform', 20);
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_codes');
    }
};
