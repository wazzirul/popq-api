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
        Schema::create('quiz_list_choice', function (Blueprint $table) {
            $table->increments('id_quiz_list_choice');
            $table->unsignedInteger('id_quiz');
            $table->foreign('id_quiz')->references('id_quiz')->on('quiz')->onDelete('cascade');
            $table->unsignedInteger('id_quiz_questions');
            $table->foreign('id_quiz_questions')->references('id_quiz_questions')->on('quiz_questions')->onDelete('cascade');
            $table->string('answer',2000);
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
        Schema::dropIfExists('quiz_list_choice');
    }
};
