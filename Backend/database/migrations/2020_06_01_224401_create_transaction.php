<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransaction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable();//we could have set this as unique to avoid extra code in our code base...
            $table->string('uid')->nullable();
            $table->float('amount');
            $table->string('payment_reference')->nullable();
            $table->string('description')->nullable();
            $table->enum('transaction_type', ['manuel_funding', 'gateway_funding', 'bank_funding', 'transfer_funding', 'airtime', 'data', 'cable', 'electricity', 'coin'])->nullable();
            $table->string('sub_service_id')->nullable();
            $table->string('packages_id')->nullable();
            $table->string('token')->nullable();
            $table->enum('status', ['success', 'failed', 'pending', 'declined'])->nullable();
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
        Schema::dropIfExists('transaction');
    }
}
