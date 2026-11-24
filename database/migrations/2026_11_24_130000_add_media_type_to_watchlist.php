<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Watchlist can hold TV shows too: existing rows are movies. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watchlist', function (Blueprint $table) {
            $table->string('media_type', 5)->default('movie')->after('tmdb_id');
            $table->index(['user_id', 'media_type', 'tmdb_id']);
        });
    }

    public function down(): void
    {
        Schema::table('watchlist', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'media_type', 'tmdb_id']);
            $table->dropColumn('media_type');
        });
    }
};
