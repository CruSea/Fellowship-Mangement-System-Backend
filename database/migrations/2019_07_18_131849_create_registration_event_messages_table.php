<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRegistrationEventMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('registration_event_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('message');
            $table->integer('event_registrations_id')->unsigned();
            $table->foreign('event_registrations_id')->references('id')->on('event_registrations')->onDelete('cascade');
            $table->integer('sms_port_id')->unsigned()->nullable();
            $table->foreign('sms_port_id')->references('id')->on('sms_ports')->onDelete('cascade');
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
        Schema::dropIfExists('registration_event_messages');
    }
}
