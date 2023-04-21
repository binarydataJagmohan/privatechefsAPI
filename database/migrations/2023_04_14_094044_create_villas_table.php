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
        Schema::create('villas', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->enum('partner_owner',['partner','owner']);
            $table->string('capacity')->nullable();
            $table->enum('category',['basic', 'luxury']);
            $table->string('price_per_day')->nullable();
            $table->string('bedrooms')->nullable();
            $table->string('bathrooms')->nullable();
            $table->enum('BBQ',['yes','no'])->default('no');
            $table->enum('type_of_stove',['gas','electric','induction'])->default('gas');
            $table->enum('equipment',['basic','fully_equipped'])->nullable();
            $table->string('consierge_phone')->nullable();
            $table->string('website')->nullable();
            $table->string('image')->nullable();
            $table->string('facebook_link')->nullable();
            $table->string('instagram_link')->nullable();
            $table->string('twitter_link')->nullable();
            $table->string('linkedin_link')->nullable();
            $table->string('youtube_link')->nullable();
            $table->enum('status', ['active', 'inactive','deleted'])->default('active');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('villas');
    }
};
