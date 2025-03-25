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
        Schema::table('ads_management', function (Blueprint $table) {
            $table->dropColumn('keywords');
            $table->dropColumn('total_ads');
            $table->longText('script')->nullable()->after('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads_management', function (Blueprint $table) {
            $table->string('keywords')->nullable()->after('slug');
            $table->integer('total_ads')->default(1)->after('keywords');
            $table->dropColumn('script');
        });
    }
};
