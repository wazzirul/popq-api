<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Poll;

return new class extends Migration
{
    public function up()
    {
        Schema::table('poll', function (Blueprint $table) {
            $table->string('slug')->after('title');
        });

        // Generate slugs for existing poll
        $polls = Poll::all();
        foreach ($polls as $poll) {
            $poll->slug = Str::slug($poll->title, '-');
            $poll->save();
        }
    }

    public function down()
    {
        Schema::table('poll', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
