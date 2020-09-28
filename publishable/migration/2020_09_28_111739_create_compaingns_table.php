<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaingnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('compaingns', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->integer('group_id',false, true)->comment('组id');
            $table->integer('owner_group_id',false, true)->comment('个人组id');
            $table->string('name', 10)->comment('名称');
            $table->integer('owner_id', false, true);
            $table->string('describe', 50)->comment('描述');
            $table->string('picture')->comment('图片');
            $table->dateTime('schedule_begin')->comment('活动开始时间');
            $table->dateTime('schedule_end')->comment('活动结束时间');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('compaingns');
    }
}
