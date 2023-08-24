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
        Schema::create('poll', function (Blueprint $table) {
            $table->increments('id_poll');
            $table->unsignedInteger('id_categories');
            $table->foreign('id_categories')->references('id_categories')->on('categories')->onDelete('cascade');
            $table->string('title');
            $table->string('subtitle');
            $table->string('thumbnail');
            $table->string('description');
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
        Schema::dropIfExists('poll');
    }
};
