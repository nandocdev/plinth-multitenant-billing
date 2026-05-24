<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Core / Independent Tables
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('provider_plan_id')->unique();
            $table->string('name');
            $table->string('currency', 3);
            $table->decimal('amount', 12, 2);
            $table->string('interval'); // MONTH, YEAR
            $table->integer('interval_count')->default(1);
            $table->timestamps();
        });

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

        Schema::create('webhook_calls', function (Blueprint $table) {
            $table->id();
            $table->json('payload');
            $table->string('event_type')->nullable();
            $table->string('status')->default('PENDING'); // PENDING, PROCESSED, FAILED
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_payment_providers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('provider');
            $table->json('credentials');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->enum('type', ['CREDIT', 'DEBIT']);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
            
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('usage_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('feature')->index();
            $table->unsignedBigInteger('total_usage');
            $table->timestamp('snapshot_at');
            $table->timestamps();

            $table->unique(['tenant_id', 'feature', 'snapshot_at']);
        });

        // 2. Billing Level 1
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('plan_id')->constrained('plans');
            $table->string('provider_subscription_id')->unique();
            $table->string('status');
            $table->timestamp('current_period_end')->nullable();
            $table->timestamps();
        });

        // 3. Billing Level 2
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('subscription_id')->constrained('subscriptions');
            $table->string('provider_invoice_id')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('status');
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->onDelete('cascade');
            $table->string('name');
            $table->decimal('amount', 12, 2);
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });

        // 4. Billing Level 3
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });

        Schema::create('billing_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('status'); // PENDING, SUCCESS, FAILED
            $table->text('error_message')->nullable();
            $table->timestamp('attempted_at')->useCurrent();
            $table->timestamps();
        });

        // 5. Payments Level 1
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

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('type'); // CARD, BANK_TRANSFER, etc.
            $table->string('token')->nullable(); // For saved cards
            $table->string('last4', 4)->nullable();
            $table->string('exp_month', 2)->nullable();
            $table->string('exp_year', 4)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // 6. Payments Level 2
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

        // 7. Payments Level 3
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->string('provider_refund_id')->unique()->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('status')->default('PENDING');
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->string('provider_dispute_id')->unique()->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('status')->default('OPEN');
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disputes');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('billing_attempts');
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('subscription_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('usage_snapshots');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('tenant_payment_providers');
        Schema::dropIfExists('webhook_calls');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('plans');
    }
};
