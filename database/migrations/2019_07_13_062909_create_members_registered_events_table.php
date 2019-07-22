<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMembersRegisteredEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('members_registered_events', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('event_registration_id')->unsigned();
            $table->foreign('event_registration_id')->references('id')->on('event_registrations')->onDelete('cascade');
            $table->integer('registered_member_id')->unsigned();
            $table->foreign('registered_member_id')->references('id')->on('registered_members')->onDelete('cascade');
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
        Schema::dropIfExists('members_registered_events');
    }
}
