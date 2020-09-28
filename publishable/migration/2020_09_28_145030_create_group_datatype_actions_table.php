<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupDatatypeActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_datatype_actions', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->integer('gd_id', false, true);
            $table->integer('group_id', false, true);
            $table->integer('action_id', false, true);
            $table->integer('datatype_id', false, true);
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
        Schema::dropIfExists('group_datatype_actions');
    }
}
