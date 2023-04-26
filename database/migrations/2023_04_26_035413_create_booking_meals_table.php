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
        Schema::create('booking_meals', function (Blueprint $table) {
            $table->id();
            $table->integer('booking_id')->nullable();
            $table->date('date')->nullable();
            $table->string('breakfast',20)->nullable();
            $table->string('lunch',20)->nullable();
            $table->string('dinner',20)->nullable();
            $table->enum('category', ['onetime', 'multipletimes'])->default('onetime');
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
        Schema::dropIfExists('booking_meals');
    }
};
