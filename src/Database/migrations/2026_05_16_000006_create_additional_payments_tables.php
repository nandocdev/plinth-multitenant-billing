<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dlocal_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('customer_id')->constrained('dlocal_customers')->onDelete('cascade');
            $table->string('type'); // CARD, BANK_TRANSFER, etc.
            $table->string('token')->nullable(); // For saved cards
            $table->string('last4', 4)->nullable();
            $table->string('exp_month', 2)->nullable();
            $table->string('exp_year', 4)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('dlocal_refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('transaction_id')->constrained('dlocal_transactions')->onDelete('cascade');
            $table->string('dlocal_refund_id')->unique()->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('status')->default('PENDING');
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('dlocal_disputes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('transaction_id')->constrained('dlocal_transactions')->onDelete('cascade');
            $table->string('dlocal_dispute_id')->unique()->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('status')->default('OPEN');
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dlocal_disputes');
        Schema::dropIfExists('dlocal_refunds');
        Schema::dropIfExists('dlocal_payment_methods');
    }
};
