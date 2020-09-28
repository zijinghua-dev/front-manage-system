<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->integer('owner_id', false, true);
            $table->integer('campaign_id', false, true);
            $table->integer('owner_group_id', false, true);
            $table->smallInteger('duration', false, true);
            $table->decimal('price', 10, 2);
            $table->string('name', 20);
            $table->tinyInteger('status');
            $table->dateTime('schedule_begin')->comment('活动开始时间');
            $table->dateTime('schedule_end')->comment('活动结束时间');
            $table->dateTime('begin')->comment('活动开始时间');
            $table->dateTime('end')->comment('活动结束时间');
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
        Schema::dropIfExists('cards');
    }
}
