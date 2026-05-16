<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dlocal_webhook_calls', function (Blueprint $table) {
            $table->id();
            $table->json('payload');
            $table->string('event_type')->nullable();
            $table->string('status')->default('PENDING'); // PENDING, PROCESSED, FAILED
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dlocal_webhook_calls');
    }
};
