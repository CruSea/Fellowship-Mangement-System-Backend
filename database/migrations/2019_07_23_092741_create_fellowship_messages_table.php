<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFellowshipMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fellowship_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('message');
            $table->integer('fellowship_id')->unsigned()->nullable();
            $table->foreign('fellowship_id')->references('id')->on('fellowships')->onDelete('cascade');
            $table->boolean('under_graduate');
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
        Schema::dropIfExists('fellowship_messages');
    }
}
