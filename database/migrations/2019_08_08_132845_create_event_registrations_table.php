<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventRegistrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('message');
            $table->integer('registration_key_id')->unsigned()->nullable();
            $table->foreign('registration_key_id')->references('id')->on('registration_keys')->onDelete('cascade');
            $table->integer('team_id')->unsigned()->nullable();
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->integer('fellowship_id')->unsigned()->nullable();
            $table->foreign('fellowship_id')->references('id')->on('fellowships')->onDelete('cascade');
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
        Schema::dropIfExists('event_registrations');
    }
}
