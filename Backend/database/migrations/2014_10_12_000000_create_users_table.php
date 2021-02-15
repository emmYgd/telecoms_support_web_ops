<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            
            $table->id();
            $table->string('name');//just in case there is any part of our codebase that uses this..
            $table->string('first_name');
            //$table->string('middle_name');
            $table->string('last_name');
             
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->enum('gender', ['Male', 'Female']);
            $table->string('state_of_residence')->nullable();
            
            $table->string('phone')->unique();
            $table->string('wallet_id')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email_verified_token')->nullable();
            
            $table->enum('email_verified_status', ['active', 'pending']);
            $table->string('password');
            $table->enum('account_type', ['user','agent']);
            $table->enum('2fa', ['on', 'off']);
            
            $table->string('referral_id')->nullable();
            $table->string('referral_upline_username')->nullable(); // person who referred
            $table->enum('membership_level', ['starter', 're-seller', 'dealer', 'exec'])->default('starter');
            
            $table->integer('bvn_verify');
            $table->string('passport');
            $table->json('bvn_data');
            
            $table->json('providus_account');
            
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
