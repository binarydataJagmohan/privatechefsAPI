<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('surname')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('view_password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->integer('email_verified')->default(0)->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('passport_no')->nullable();
            $table->date('timezone')->nullable();
            $table->string('currency')->nullable();
            $table->date('birthday')->nullable();
            $table->integer('login_count')->default(0)->nullable();
            $table->dateTime('last_login')->nullable();
            $table->text('pic')->nullable();
            $table->enum('invoice_details', ['0', '1'])->nullable()->default('0');
            $table->string('company_name')->nullable();
            $table->string('vat_no')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('BIC')->nullable();
            $table->string('IBAN')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('holder_name')->nullable();
            $table->string('bank_address')->nullable();
            $table->enum('role', ['admin', 'superadmin', 'chef', 'user', 'concierge'])->default('user');
            $table->boolean('first_login')->default(false)->nullable();
            $table->enum('status', ['active', 'deactive','deleted'])->default('active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
