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
        Schema::table('quiz', function (Blueprint $table) {
            $table->string('topic')->nullable()->after('id_categories');
            $table->string('images_id')->nullable()->after('subtitle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('quiz', function (Blueprint $table) {
            $table->dropColumn('topic');
            $table->dropColumn('images_id');
        });
    }
};
