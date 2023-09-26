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
        //
        Schema::table('user_quiz', function (Blueprint $table) {
            $table->string('answered')->nullable()->after('id_quiz');
            $table->smallInteger('status')->default(0)->nullable()->after('answered');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('user_quiz', function (Blueprint $table) {
            $table->dropColumn('answered');
            $table->dropColumn('completed');
        });
    }
};
