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
            $table->text('moderator_comment')->nullable();
            $table->text('client_comment')->nullable();
            $table->text('reviewed_answer')->nullable();
            $table->text('reviewed_rejected_type')->nullable();
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
