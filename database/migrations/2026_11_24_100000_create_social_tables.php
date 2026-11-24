<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Envydeo Social: profiles, followers (with requests for private accounts), blocks,
 * "Tonight" check-ins (+ reactions), diary entries (ratings & reviews + likes),
 * the activity feed, and content reports.
 *
 * "Friends" = mutual accepted follows. Check-ins are friends-only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable()->unique()->after('name');
            $table->string('bio', 160)->nullable()->after('username');
            $table->boolean('is_private')->default(false)->after('bio');
            $table->json('top_films')->nullable()->after('is_private'); // up to 4 movie snapshots
            $table->boolean('notify_friend_checkins')->default(true)->after('top_films');
        });

        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('followee_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 10)->default('accepted'); // accepted | pending (private accounts)
            $table->timestamps();

            $table->unique(['follower_id', 'followee_id']);
            $table->index(['followee_id', 'status']);
        });

        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('blocker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('blocked_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['blocker_id', 'blocked_id']);
        });

        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('tmdb_id');
            $table->string('media_type', 5)->default('movie'); // movie | tv
            $table->json('movie'); // snapshot: title, poster_path, backdrop_path, year
            $table->string('caption', 140)->nullable();
            $table->string('photo_path')->nullable(); // private disk; served via an authorised route
            $table->timestamp('expires_at')->index(); // leaves the "Tonight" strip after 24h
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('check_in_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('check_in_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('reaction', 12); // popcorn | laugh | cry | scream | love | in
            $table->timestamps();

            $table->unique(['check_in_id', 'user_id']);
        });

        Schema::create('diary_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('tmdb_id');
            $table->string('media_type', 5)->default('movie');
            $table->json('movie');
            $table->unsignedTinyInteger('rating')->nullable(); // 1–10 = ½ to 5 stars
            $table->text('review')->nullable();
            $table->boolean('has_spoilers')->default(false);
            $table->date('watched_on');
            $table->timestamps();

            $table->index(['tmdb_id', 'media_type']);
            $table->index(['user_id', 'watched_on']);
        });

        Schema::create('review_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diary_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['diary_entry_id', 'user_id']);
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete(); // who did it
            $table->string('type', 20); // checked_in | logged | watchlisted | matched | followed
            $table->nullableMorphs('subject');
            $table->json('data')->nullable(); // snapshot for rendering without extra queries
            $table->string('visibility', 10)->default('public'); // public | friends
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            // Reports can target users (UUID ids) as well as check-ins/reviews (numeric ids)
            $table->string('reportable_type');
            $table->string('reportable_id', 36);
            $table->index(['reportable_type', 'reportable_id']);
            $table->string('reason', 30);
            $table->string('details', 500)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('review_likes');
        Schema::dropIfExists('diary_entries');
        Schema::dropIfExists('check_in_reactions');
        Schema::dropIfExists('check_ins');
        Schema::dropIfExists('blocks');
        Schema::dropIfExists('follows');
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'bio', 'is_private', 'top_films', 'notify_friend_checkins']);
        });
    }
};
