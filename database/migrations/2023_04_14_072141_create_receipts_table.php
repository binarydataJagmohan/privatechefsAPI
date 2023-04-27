<?php

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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->decimal('total_cost')->nullable();
            $table->string('payment_details')->nullable();
            $table->date('order_date')->nullable();
            $table->enum('payment_type', ['paypal', 'credit_card', 'bitcoin', 'paymino'])->default('paypal');
            $table->enum('status', ['active', 'deactive','deleted'])->default('active');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
