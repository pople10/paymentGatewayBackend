<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVCCSTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vcc', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('idUser')->references('idUser')->on('users')->constrained()->onUpdate('CASCADE')->onDelete('RESTRICT');
            $table->string("number",16)->unique();
            $table->string("expiringDate");
            $table->string("cvv");
            $table->string('idCurrency',3)->references('idCurrency')->on('currencies')->constrained()->onUpdate('CASCADE')->onDelete('RESTRICT');
            $table->boolean("active")->default(true);
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
        Schema::dropIfExists('v_c_c_s');
    }
}
