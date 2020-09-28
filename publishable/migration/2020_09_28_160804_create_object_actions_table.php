<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObjectActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('object_actions', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->integer('group_id', false, true);
            $table->integer('object_id', false, true);
            $table->integer('datatype_id', false, true);
            $table->integer('action_id', false, true);
            $table->integer('from_group_id', false, true);
            $table->tinyInteger('enabled');
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
        Schema::dropIfExists('object_actions');
    }
}
