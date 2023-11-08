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
        Schema::create('quiz', function (Blueprint $table) {
            $table->increments('id_quiz');
            $table->unsignedInteger('id_categories');
            $table->foreign('id_categories')->references('id_categories')->on('categories')->onDelete('cascade');
            $table->string('title', 2000);
            $table->string('subtitle', 2000);
            $table->string('thumbnail', 2000);
            $table->string('description', 2000);
            $table->boolean('today_status')->default(false);
            $table->boolean('draft')->default(false);

            $table->smallInteger('soft_delete')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz');
    }
};
