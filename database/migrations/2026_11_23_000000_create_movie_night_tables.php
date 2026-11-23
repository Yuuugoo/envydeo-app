<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Movie Night Matcher: a shared room where participants swipe the same
     * deck of movies until everyone likes the same one.
     */
    public function up(): void
    {
        Schema::create('match_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->string('status', 16)->default('lobby'); // lobby | voting | matched
            $table->string('source', 16)->default('discover'); // discover | watchlist
            $table->string('theme', 32)->nullable();          // key from App\Support\MovieNightThemes
            $table->boolean('is_persistent')->default(false); // "Us" rooms: never expire, keep collecting matches
            $table->json('filters');
            $table->json('candidates'); // deck: [{id, title, poster_path, ...}]
            $table->unsignedSmallInteger('next_page')->default(4); // next TMDB discover page to deal from
            $table->unsignedBigInteger('matched_tmdb_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index(); // null = persistent
            $table->timestamps();
        });

        Schema::create('match_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_room_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 40);
            $table->string('token', 64)->unique(); // sha256 of the secret kept in the participant's session
            $table->boolean('is_host')->default(false);
            $table->timestamps();

            $table->index(['match_room_id', 'user_id']);
        });

        Schema::create('match_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('match_participant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('tmdb_id');
            $table->boolean('liked');
            $table->timestamps();

            $table->unique(['match_participant_id', 'tmdb_id']);
            $table->index(['match_room_id', 'tmdb_id', 'liked']);
        });

        // Every unanimous like. Regular rooms end at the first one; "Us" rooms keep a growing list.
        Schema::create('match_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_room_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('tmdb_id');
            $table->json('movie'); // snapshot of the card so the list needs no TMDB calls
            $table->timestamp('watched_at')->nullable();
            $table->timestamps();

            $table->unique(['match_room_id', 'tmdb_id']);
        });

        // Anonymous product analytics (no IPs, names or user ids).
        Schema::create('movie_night_events', function (Blueprint $table) {
            $table->id();
            $table->string('event', 32)->index();
            $table->foreignId('match_room_id')->nullable()->constrained()->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movie_night_events');
        Schema::dropIfExists('match_results');
        Schema::dropIfExists('match_votes');
        Schema::dropIfExists('match_participants');
        Schema::dropIfExists('match_rooms');
    }
};
