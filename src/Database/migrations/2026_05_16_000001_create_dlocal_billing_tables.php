<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dlocal_plans', function (Blueprint $table) {
            $table->id();
            $table->string('dlocal_plan_id')->unique();
            $table->string('name');
            $table->string('currency', 3);
            $table->decimal('amount', 12, 2);
            $table->string('interval'); // MONTH, YEAR
            $table->integer('interval_count')->default(1);
            $table->timestamps();
        });

        Schema::create('dlocal_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('plan_id')->constrained('dlocal_plans');
            $table->string('dlocal_subscription_id')->unique();
            $table->string('status');
            $table->timestamp('current_period_end')->nullable();
            $table->timestamps();
        });

        Schema::create('dlocal_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('subscription_id')->constrained('dlocal_subscriptions');
            $table->string('dlocal_invoice_id')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('status');
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dlocal_invoices');
        Schema::dropIfExists('dlocal_subscriptions');
        Schema::dropIfExists('dlocal_plans');
    }
};
