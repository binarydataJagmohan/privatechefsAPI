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
        Schema::create('chef_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('about')->nullable();
            $table->longText('description')->nullable();
            $table->string('services_type')->nullable();
            $table->string('employment_status')->nullable();
            $table->string('website')->nullable();
            $table->string('languages')->nullable();
            $table->string('experience')->nullable();
            $table->string('skills')->nullable();
            $table->string('love_cooking')->nullable();
            $table->string('favorite_chef')->nullable();
            $table->string('favorite_dishes')->nullable();
            $table->string('cooking_des')->nullable();
            $table->string('facebook_link')->nullable();
            $table->string('instagram_link')->nullable();
            $table->string('twitter_link')->nullable();
            $table->string('linkedin_link')->nullable();
            $table->string('youtube_link')->nullable();
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
        Schema::dropIfExists('chef_details');
    }
};
