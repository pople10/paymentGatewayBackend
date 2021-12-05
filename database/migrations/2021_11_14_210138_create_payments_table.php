<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->string('idPayment')->primary();
            $table->foreignId('from')->references('idUser')->on('users')->constrained()->onUpdate('CASCADE')->onDelete('RESTRICT');
            $table->foreignId('to')->references('idUser')->on('users')->constrained()->onUpdate('CASCADE')->onDelete('RESTRICT');
            $table->string('currency',3)->references('idCurrency')->on('currencies')->constrained()->onUpdate('CASCADE')->onDelete('RESTRICT');
            $table->float("amount");
            $table->foreignId('status')->references('idStatus')->on('statuses')->constrained()->onUpdate('CASCADE')->onDelete('RESTRICT');
            $table->foreignId('type')->references('idType')->on('paymentType')->constrained()->onUpdate('CASCADE')->onDelete('RESTRICT');
            $table->string("title");
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
        Schema::dropIfExists('payments');
    }
}
