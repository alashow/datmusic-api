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
        Schema::connection('postgres')->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('client_id')->unique();
            $table->string('client_version')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->string('fcm_token')->nullable();
            $table->integer('update_count')->default(0);
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
        Schema::connection('postgres')->dropIfExists('users');
    }
}
