<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCountMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('count_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('count');
            $table->string('type');
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
        Schema::dropIfExists('count_messages');
    }
}
