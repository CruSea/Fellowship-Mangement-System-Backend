<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTodayMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('today_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('message');
            $table->integer('remaining_time');
            $table->integer('alarm_message_id')->unsigned()->nullable();
            $table->foreign('alarm_message_id')->references('id')->on('alarm_messages')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('schedule_message_id')->unsigned()->nullable();
            $table->foreign('schedule_message_id')->references('id')->on('schedule_messages')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('fellowship_id')->unsigned()->nullable();
            $table->foreign('fellowship_id')->references('id')->on('fellowships')->onDelete('cascade');
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
        Schema::dropIfExists('today_messages');
    }
}
