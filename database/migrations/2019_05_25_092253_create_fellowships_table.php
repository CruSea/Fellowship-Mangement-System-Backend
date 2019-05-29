<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFellowshipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fellowships', function (Blueprint $table) {
            $table->increments('id');
            $table->string('university_name');
            $table->string('university_city');
            $table->string('specific_place')->nullable();
            $table->integer('number_of_members');
            $table->integer('number_of_groups');
            $table->timestamps();
        });
    }
// User:model
    // Schema::create('users', function (Blueprint $table) {
    //     $table->increments('id');
    //     // $table->string('firstname');
    //     // $table->string('lastname');
    //     $table->string('full_name');
    //     $table->string('phone')->unique();
    //     $table->boolean('status')->default(false);
    //     $table->integer('fellowship_id')->unsigned()->nullable();
    //     $table->foreign('fellowship_id')->references('id')->on('fellowships')->onDelete('cascade');
    //     $table->string('email')->unique();
    //     $table->string('password');
    //     $table->rememberToken();
    //     $table->timestamps();
    // });
    ////--------------------------------------------------
// Team:model
    // Schema::create('teams', function (Blueprint $table) {
    //     $table->increments('id');
    //     $table->string('name')->unique();
    //     $table->string('description')->nullable();
    //     $table->string('number_of_contacts');
    //     $table->integer('fellowship_id')->unsigned()->nullable();
    //     $table->foreign('fellowship_id')->references('id')->on('fellowships')->onDelete('cascade');
    //     $table->timestamps();
    // });
    ////---------------------------------------------------
// Contact:model
    // Schema::create('contacts', function (Blueprint $table) {
    //     $table->increments('id');
    //     // $table->string('firstname');
    //     // $table->string('lastname');
    //     $table->string('full_name');
    //     $table->string('gender');
    //     $table->string('phone');
    //     // $table->string('university');
    //     $table->string('Acadamic_department')->nullable();
    //     $table->integer('fellowship_id')->unsigned()->nullable();
    //     $table->foreign('fellowship_id')->references('id')->on('fellowships')->onDelete('cascade');
    //     $table->timestamps();
    // });
    ////----------------------------------------------------------
// SentMessage:model
    // Schema::create('sent_messages', function (Blueprint $table) {
    //     $table->increments('id');
    //     $table->string('message');
    //     $table->string('sent_to');
    //     $table->string('status');
    //     $table->string('sent_by');
    //     $table->timestamps();
    // });
    ////---------------------------------------------------------
// ContactTeam:model
    // Schema::create('contact_teams', function (Blueprint $table) {
    //     $table->increments('id');
    //     $table->integer('team_id')->unsigned();
    //     $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
    //     $table->integer('contact_id')->unsigned();
    //     $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
    //     $table->timestamps();
    // });
    ////----------------------------------------------------------------------
// TeamMessage:model
    // Schema::create('team_messages', function (Blueprint $table) {
    //     $table->increments('id');
    //     $table->string('message');
    //     $table->string('team_name');
    //     $table->integer('sent_msg_count');
    //     $table->timestamps();
    // });
    /*************************************************************************** */
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fellowships');
    }
}
