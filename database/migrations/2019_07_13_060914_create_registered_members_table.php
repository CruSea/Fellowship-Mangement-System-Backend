<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRegisteredMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('registered_members', function (Blueprint $table) {
            $table->increments('id');
            $table->string('phone')->unique();
            // $table->string('full_name');
            // $table->string('gender');
            // $table->string('acadamic_department');
            // $table->string('acadamic_year');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('registered_members');
    }
}
