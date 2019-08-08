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
            $table->string('registration_key');
            $table->string('type');
            $table->string('event')->nullable();
            $table->boolean('for_contact_update')->default(false);
            $table->string('message');
            $table->string('success_message')->nullable();
            $table->string('failed_message')->nullable();
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
