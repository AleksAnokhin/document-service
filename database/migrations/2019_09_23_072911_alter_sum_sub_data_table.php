<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSumSubDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('sum_sub_data', function (Blueprint $table) {
            $table->dropColumn('on_hold');
            $table->dropColumn('prechecked');
        });



        Schema::table('sum_sub_data', function (Blueprint $table) {
            $table->integer('created')->nullable();
            $table->integer('on_hold')->nullable();
            $table->integer('prechecked')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
