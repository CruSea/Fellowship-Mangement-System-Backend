<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactTeamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_teams', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('team_id')->unsigned();
        $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        $table->integer('contact_id')->unsigned();
        $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
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
        Schema::dropIfExists('contact_teams');
    }
}
