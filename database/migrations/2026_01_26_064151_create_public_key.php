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
        Schema::create('public_key', function (Blueprint $table) {
            $table->increments('id_public_key');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('public_key')->unique();
            $table->string('reset_password');

            $table->smallInteger('soft_delete')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_key');
    }
};
