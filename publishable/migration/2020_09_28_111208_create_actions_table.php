<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('actions', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('name', 10)->comment('操作名');
            $table->string('alias', 10)->comment('别名');
            $table->string('describe', 50)->comment('描述');
            $table->tinyInteger('ternary');
            $table->integer('ternary_id',false, true);
            $table->tinyInteger('depend_object');
            $table->tinyInteger('only_group');
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
        Schema::dropIfExists('actions');
    }
}
