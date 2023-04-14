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
            $table->unsignedBigInteger('services_id')->nullable();
            $table->date('booking_date')->nullable();
            $table->string('location')->nullable();
            $table->integer('adult')->nullable();
            $table->integer('children')->nullable();
            $table->integer('teen')->nullable();
            $table->text('booking_location')->nullable();
            $table->text('chef_offer')->nullable();
            $table->unsignedBigInteger('assigned_to_user_id')->nullable();
            $table->enum('category', ['one_time', 'multiple_times'])->default('one_time');
            $table->enum('status', ['completed', 'upcoming', 'canceled'])->default('upcoming');
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
