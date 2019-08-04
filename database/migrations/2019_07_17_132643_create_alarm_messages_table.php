<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAlarmMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('alarm_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->date('send_date');
            $table->time('send_time');
            $table->string('message');
            $table->integer('sms_port_id')->unsigned()->nullable();
            $table->foreign('sms_port_id')->references('id')->on('sms_ports')->onDelete('cascade');
            $table->integer('team_id')->unsigned()->nullable();
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->integer('fellowship_id')->unsigned()->nullable();
            $table->foreign('fellowship_id')->references('id')->on('fellowships')->onDelete('cascade');
            $table->integer('event_id')->unsigned()->nullable();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->string('phone')->nullable();
            $table->string('sent_to');
            $table->integer('get_fellowship_id')->unsigned()->nullable();
            $table->foreign('get_fellowship_id')->references('id')->on('fellowships')->onDelete('cascade');
            $table->json('sent_by');
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
        Schema::dropIfExists('alarm_messages');
    }
}
