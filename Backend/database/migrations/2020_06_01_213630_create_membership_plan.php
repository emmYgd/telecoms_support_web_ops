<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMembershipPlan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('membership_plan', function (Blueprint $table) {
            $table->id();
            $table->enum('id', ['starter', 're-seller', 'dealer', 'exec']);
            $table->string('name');
            $table->float('upgrade_amount');
            $table->string('discount_amount');
            $table->string('ns_coin_discount_amount');
            $table->string('airtime_discount')->nullable();
            $table->string('data_discount')->nullable();
            $table->string('cable_discount')->nullable();
            $table->string('electricity_discount')->nullable();
            $table->string('direct_down_line_data_commission')->nullable();
            $table->string('direct_down_line_referral_commission')->nullable();
            $table->string('in_direct_down_line_referral_commission')->nullable();
            $table->string('other_generation_commission')->nullable();
            $table->string('last_generation')->nullable();
            $table->string('subscription_bonus')->nullable();
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
        Schema::dropIfExists('membership_plan');
    }
}
