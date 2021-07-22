<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MinervaCreate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('minerva')->create('audios', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('source_id');
            $table->string('artist');
            $table->string('title');
            $table->string('album')->nullable();
            $table->integer('duration')->default(0);
            $table->timestamp('date')->default(0);
            $table->string('cover_url')->nullable();
            $table->string('cover_url_medium')->nullable();
            $table->string('cover_url_small')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('minerva')->drop('audios');
    }
}
