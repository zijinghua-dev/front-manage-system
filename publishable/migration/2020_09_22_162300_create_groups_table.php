<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable(true);
            $table->string('describe')->nullable(true);
            $table->bigInteger('owner_id')->nullable(true);
            $table->bigInteger('owner_group_id')->nullable(true);
            $table->string('picture')->nullable(true);
            $table->bigInteger('datatype_id')->nullable(true);
            $table->bigInteger('object_id')->nullable(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('organizes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable(true);
            $table->string('picture')->nullable(true);
            $table->bigInteger('group_id')->nullable(true);
            $table->string('describe')->nullable(true);
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
        Schema::dropIfExists('groups');
    }
}
