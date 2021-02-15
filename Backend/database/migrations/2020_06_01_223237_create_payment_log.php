<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_log', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->nullable();
            $table->string('funding_type')->nullable();
            $table->string('funding_by')->nullable();
            $table->float('amount');
            $table->string('gateway_fee')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('payment_id')->nullable();
            $table->string('gateway_id')->nullable();
            $table->enum('status', ['success', 'declined', 'pending', 'failed'])->nullable();
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
        Schema::dropIfExists('payment_log');
    }
}
