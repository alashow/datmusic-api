<?php
/*
 * Copyright (c) 2021  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MinervaAddIsExplicit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('minerva')->table('audios', function (Blueprint $table) {
            $table->boolean('is_explicit')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('minerva')->table('audios', function (Blueprint $table) {
            $table->dropColumn('is_explicit');
        });
    }
}
