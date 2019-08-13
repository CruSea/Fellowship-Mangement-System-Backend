<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRegistrationKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('registration_keys', function (Blueprint $table) {
            $table->increments('id');
            $table->string('registration_key');
            $table->string('type');
            $table->string('event')->nullable();
            $table->boolean('for_contact_update')->default(false);
            $table->string('success_message_reply')->nullabe();
            // $table->string('failed_message_reply')->nullable();
            $table->date('registration_end_date');
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
        Schema::dropIfExists('registration_keys');
    }
}
