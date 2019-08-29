<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSentMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sent_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('message');
            $table->string('sent_to');
            // $table->string('status');
            $table->boolean('is_sent');
            $table->boolean('is_delivered');
            $table->integer('sms_port_id')->unsigned()->nullable();
            $table->foreign('sms_port_id')->references('id')->on('sms_ports')->onDelete('cascade');
            $table->integer('fellowship_id')->unsigned()->nullable();
            $table->foreign('fellowship_id')->references('id')->on('fellowships')->onDelete('cascade');
            $table->boolean('is_removed')->default(false);
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
        Schema::dropIfExists('sent_messages');
    }
}
