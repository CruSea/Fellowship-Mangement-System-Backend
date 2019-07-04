<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
        $table->increments('id');
        // $table->string('firstname');
        // $table->string('lastname');
        $table->string('full_name');
        $table->string('gender');
        $table->string('phone');
        // $table->string('university');
        $table->string('Acadamic_department')->nullable();
        $table->integer('fellowship_id')->unsigned()->nullable();
        $table->foreign('fellowship_id')->references('id')->on('fellowships')->onDelete('cascade');
        $table->json('created_by');
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
        Schema::dropIfExists('contacts');
    }
}
