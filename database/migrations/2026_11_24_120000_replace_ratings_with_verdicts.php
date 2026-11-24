<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Envydeo verdicts replace ½-star ratings: loved | good | meh | skip, plus a "best with friends" tag.
 * Existing ratings (1–10) are converted: 9–10 loved, 7–8 good, 5–6 meh, 1–4 skip.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diary_entries', function (Blueprint $table) {
            $table->string('verdict', 8)->nullable()->after('movie');
            $table->boolean('best_with_friends')->default(false)->after('verdict');
        });

        DB::table('diary_entries')->whereNotNull('rating')->update([
            'verdict' => DB::raw("CASE WHEN rating >= 9 THEN 'loved' WHEN rating >= 7 THEN 'good' WHEN rating >= 5 THEN 'meh' ELSE 'skip' END"),
        ]);

        Schema::table('diary_entries', function (Blueprint $table) {
            $table->dropColumn('rating');
        });
    }

    public function down(): void
    {
        Schema::table('diary_entries', function (Blueprint $table) {
            $table->unsignedTinyInteger('rating')->nullable()->after('movie');
        });

        DB::table('diary_entries')->whereNotNull('verdict')->update([
            'rating' => DB::raw("CASE verdict WHEN 'loved' THEN 10 WHEN 'good' THEN 8 WHEN 'meh' THEN 5 ELSE 2 END"),
        ]);

        Schema::table('diary_entries', function (Blueprint $table) {
            $table->dropColumn(['verdict', 'best_with_friends']);
        });
    }
};
