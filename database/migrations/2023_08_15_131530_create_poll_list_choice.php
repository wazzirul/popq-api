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
        Schema::create('poll_list_choice', function (Blueprint $table) {
            $table->increments('id_poll_list_choice');
            $table->unsignedInteger('id_poll');
            $table->foreign('id_poll')->references('id_poll')->on('poll')->onDelete('cascade');
            $table->string('poll_choice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poll_list_choice');
    }
};
