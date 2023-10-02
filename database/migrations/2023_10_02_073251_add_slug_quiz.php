<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Quiz;

return new class extends Migration
{
    public function up()
    {
        Schema::table('quiz', function (Blueprint $table) {
            $table->string('slug')->after('title');
        });

        // Generate slugs for existing quiz
        $quiz = Quiz::all();
        foreach ($quizs as $quiz) {
            $quiz->slug = Str::slug($quiz->title, '-');
            $quiz->save();
        }
    }

    public function down()
    {
        Schema::table('quiz', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};

