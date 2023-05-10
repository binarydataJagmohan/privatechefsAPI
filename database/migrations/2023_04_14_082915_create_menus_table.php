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
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('cuisine_id')->nullable();
            $table->string('menu_name')->nullable();
            $table->string('image')->nullable();
            $table->longText('description')->nullable();
            $table->integer('min_person')->nullable();
            $table->integer('max_person')->nullable();
            $table->integer('min_price')->nullable();
            $table->integer('max_price')->nullable();
            $table->integer('discount')->nullable();
            $table->text('comments')->nullable();
            $table->integer('starter_items',1)->default(0);
            $table->integer('firstcourse_items',1)->default(0);
            $table->integer('maincourse_items',1)->default(0);
            $table->integer('desert_items',1)->default(0);
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
        Schema::dropIfExists('menus');
    }
};
