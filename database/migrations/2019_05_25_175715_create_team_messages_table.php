<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_messages', function (Blueprint $table) {
        $table->increments('id');
        $table->string('message');
        $table->integer('team_id')->unsigned();
        $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        $table->string('sent_by');
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
        Schema::dropIfExists('team_messages');
    }
}
