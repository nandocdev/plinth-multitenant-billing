<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dlocal_subscription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('dlocal_subscriptions')->onDelete('cascade');
            $table->string('name');
            $table->decimal('amount', 12, 2);
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });

        Schema::create('dlocal_billing_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('dlocal_invoices')->onDelete('cascade');
            $table->string('status'); // PENDING, SUCCESS, FAILED
            $table->text('error_message')->nullable();
            $table->timestamp('attempted_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dlocal_billing_attempts');
        Schema::dropIfExists('dlocal_subscription_items');
    }
};
