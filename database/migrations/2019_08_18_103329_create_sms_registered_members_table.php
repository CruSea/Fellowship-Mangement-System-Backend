<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSmsRegisteredMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sms_registered_members', function (Blueprint $table) {
            $table->increments('id');
            $table->string('full_name');
            $table->string('phone');
            $table->string('key');
            $table->string('event');
            $table->date('registered_date');
            $table->date('registration_end_date');
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
        Schema::dropIfExists('sms_registered_members');
    }
}
