<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // One row per AI request made through the gateway
        Schema::create('usage_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('token_id')->nullable()->index();   // the device that made the request
            $table->string('kind', 20);                                     // message | design | voice
            $table->string('turn_id', 64)->nullable();                      // one user question; several model calls share it
            $table->string('model', 60)->nullable();
            $table->string('response_id', 100)->nullable()->index();        // OpenAI response id, for previous_response_id ownership checks
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedSmallInteger('searches')->default(0);
            $table->decimal('voice_minutes', 8, 3)->default(0);
            $table->decimal('cost_usd', 10, 6)->default(0);
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_events');
    }
};
