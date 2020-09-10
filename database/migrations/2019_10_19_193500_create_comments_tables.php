<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommentsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');

		$table->unsignedInteger('user_id')->index();
		$table->foreign('user_id')
		    ->references('id')->on('users')
		    ->onDelete('cascade');

		$table->string('comment')->nullable();

		$table->timestamps();
		$table->softDeletes();

        });

        Schema::create('event_comment', function (Blueprint $table) {            
            $table->unsignedInteger('event_id')->index();
            $table->foreign('event_id')
              ->references('id')->on('events')
              ->onDelete('cascade');

            $table->unsignedInteger('comment_id')->index();
            $table->foreign('comment_id')
              ->references('id')->on('comments')
              ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_comment');
        Schema::dropIfExists('comments');
    }
}
