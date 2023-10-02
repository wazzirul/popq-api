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
        Schema::table('user_quiz_answer', function (Blueprint $table) {
            // Make id_quiz_list_choice column nullable
            $table->unsignedInteger('id_quiz_list_choice')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_quiz_answer', function (Blueprint $table) {
            // Revert the change (make it non-nullable)
            $table->unsignedInteger('id_quiz_list_choice')->nullable(false)->change();
        });
    }
};
