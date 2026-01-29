<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('public_key', function (Blueprint $table) {

            if (Schema::hasColumn('public_key', 'reset_password')) {
                $table->dropColumn('reset_password');
            }

            if (!Schema::hasColumn('public_key', 'reset_token')) {
                $table->string('reset_token')->nullable();
            }

            if (!Schema::hasColumn('public_key', 'reset_token_expires_at')) {
                $table->timestamp('reset_token_expires_at')
                    ->nullable()
                    ->after('reset_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('public_key', function (Blueprint $table) {

            if (Schema::hasColumn('public_key', 'reset_token_expires_at')) {
                $table->dropColumn('reset_token_expires_at');
            }

            if (Schema::hasColumn('public_key', 'reset_token')) {
                $table->dropColumn('reset_token');
            }

            if (!Schema::hasColumn('public_key', 'reset_password')) {
                $table->string('reset_password');
            }
        });
    }
};
