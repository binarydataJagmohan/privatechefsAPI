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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('service_id',20)->nullable();
            $table->string('cuisine_id',50)->nullable();
            $table->string('allergies_id',50)->nullable();
            $table->string('name')->nullable();
            $table->string('surname')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->string('location')->nullable();
            $table->string('lat',50)->nullable();
            $table->string('lng',50)->nullable();
            $table->integer('adults')->nullable();
            $table->integer('childrens')->nullable();
            $table->integer('teens')->nullable();
            $table->text('chef_offer')->nullable();
            $table->string('charge_id', 100)->nullable();
            $table->string('charge_amount')->nullable();
            $table->text('charge_receipt_url')->nullable();
            $table->string('charge_created_date')->nullable();
            $table->enum('payment_status', ['completed', 'pending', 'canceled'])->default('pending');
            $table->unsignedBigInteger('assigned_to_user_id')->nullable();
            $table->enum('booking_status', ['completed', 'upcoming', 'canceled'])->default('upcoming');
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
        Schema::dropIfExists('bookings');
    }
};
