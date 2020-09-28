<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('name', 20);
            $table->integer('owner_id', false, true);
            $table->integer('owner_group_id', false, true);
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
        Schema::dropIfExists('sessions');
    }
}
