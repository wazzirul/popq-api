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
        Schema::create('user_quiz_answer', function (Blueprint $table) {
            $table->increments('id_user_quiz_answer');
            $table->unsignedInteger('id_user_quiz');
            $table->foreign('id_user_quiz')->references('id_user_quiz')->on('user_quiz')->onDelete('cascade');
            $table->unsignedInteger('id_quiz_questions');
            $table->foreign('id_quiz_questions')->references('id_quiz_questions')->on('quiz_questions')->onDelete('cascade');
            $table->unsignedInteger('id_quiz_list_choice');
            $table->foreign('id_quiz_list_choice')->references('id_quiz_list_choice')->on('quiz_list_choice')->onDelete('cascade');
            $table->boolean('correct')->default(false);

            $table->smallInteger('soft_delete')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_quiz_answer');
    }
};
