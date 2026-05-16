<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->string('email');
            $table->string('document_type')->nullable();
            $table->string('document_number')->nullable();
            $table->string('provider_customer_id')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('customer_id')->constrained('customers');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('description')->nullable();
            $table->string('status')->default('PENDING');
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('order_id')->constrained('orders');
            $table->string('provider_id')->unique()->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('country', 2);
            $table->string('status')->default('PENDING');
            $table->string('payment_method_id')->nullable();
            $table->json('last_webhook_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('customers');
    }
};
