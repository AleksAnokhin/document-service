<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBusinessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->string('name');
            $table->integer('reg_num');
            $table->integer('country_legal');
            $table->string('biz_profile');
            $table->string('city_legal');
            $table->string('street_legal');
            $table->string('zip_legal');
            $table->string('country_actual');
            $table->string('city_actual');
            $table->string('street_actual');
            $table->string('zip_actual');
            $table->string('ben1_name');
            $table->string('ben1_surname');
            $table->string('ben2_name')->nullable();
            $table->string('ben2_surname')->nullable();
            $table->string('ben3_name')->nullable();
            $table->string('ben3_surname')->nullable();
            $table->string('dir_name');
            $table->string('dir_surname');
            $table->string('tel_prefix');
            $table->string('tel_time');
            $table->integer('pep');
            $table->integer('us');
        });

        Schema::table('business', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business');
    }
}
