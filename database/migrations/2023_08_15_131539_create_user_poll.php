<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_poll', function (Blueprint $table) {
            $table->increments('id_user_poll');
            $table->unsignedInteger('id_user');
            $table->foreign('id_user')->references('id_user')->on('users')->onDelete('cascade');
            $table->unsignedInteger('id_poll');
            $table->foreign('id_poll')->references('id_poll')->on('poll')->onDelete('cascade');
            $table->unsignedInteger('id_poll_list_choice');
            $table->foreign('id_poll_list_choice')->references('id_poll_list_choice')->on('poll_list_choice')->onDelete('cascade');
            $table->dateTime('date_time');

            $table->smallInteger('soft_delete')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_poll');
    }
};
