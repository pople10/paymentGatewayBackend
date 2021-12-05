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
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id("idUser");
            $table->string("firstname");
            $table->string("lastname");
            $table->string("phone");
            $table->string("title");
            $table->string("cin");
            $table->date("birth");
            $table->string("email")->unique();
            $table->string("username")->unique();
            $table->string("photo")->nullable()->default(null);
            $table->string("company")->nullable();
            $table->string("password",1024);
            $table->string("fireBaseToken",1024)->nullable()->default(null);
            $table->string("mac",17);
            $table->string("IMEI",1024)->nullable()->default(null);
            $table->boolean("verified")->default(false);
            $table->boolean("enabled")->default(true);
            $table->string("vkey",1024);
            $table->boolean("enabledNotification");
            $table->integer("vccLimit")->default(3);
            $table->string('api_token', 80)->unique()->nullable()->default(null);
            $table->integer("loginAttemps")->default(0);
            $table->foreignId('accType')->references('idType')->on('accountType')->constrained()->onUpdate('CASCADE')->onDelete('RESTRICT');
            $table->boolean("deleted")->default(false);
            $table->boolean("enabledReceiving")->default(true);
            $table->boolean("enabledSending")->default(true);
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
