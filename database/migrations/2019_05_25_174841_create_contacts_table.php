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
        $table->string('full_name');
        $table->string('gender');
        $table->string('phone');
        $table->string('email')->unique()->nullable();
        $table->string('Acadamic_department')->nullable();
        $table->date('graduation_year');
        $table->integer('is_under_graduate');
        $table->integer('is_this_year_gc');
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
