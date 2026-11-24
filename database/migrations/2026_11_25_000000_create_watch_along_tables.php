<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Watch Along: friends watch the same title at the same time on their own services.
 * Envydeo keeps a shared movie clock and timestamped reactions. It never streams video.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watch_parties', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->unsignedBigInteger('tmdb_id');
            $table->string('media_type', 5)->default('movie'); // movie | tv
            $table->unsignedSmallInteger('season')->nullable();  // tv only
            $table->unsignedSmallInteger('episode')->nullable(); // tv only
            $table->json('movie');                                 // title snapshot
            $table->unsignedInteger('runtime_seconds');
            $table->string('status', 10)->default('lobby');      // lobby | playing | paused | ended
            // Shared clock: position = base_ms (+ now − resumed_at_ms while playing). resumed_at_ms may be in
            // the future: that is the "3, 2, 1, press play" countdown.
            $table->unsignedBigInteger('base_ms')->default(0);
            $table->unsignedBigInteger('resumed_at_ms')->nullable();
            $table->string('last_event', 80)->nullable();          // "Ben paused", shown to everyone
            $table->unsignedBigInteger('last_event_at_ms')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });

        Schema::create('watch_party_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('watch_party_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 40);
            $table->string('token', 64)->unique(); // sha256 of the secret kept in the member's session
            $table->boolean('is_host')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['watch_party_id', 'user_id']);
        });

        Schema::create('watch_party_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('watch_party_id')->constrained()->cascadeOnDelete();
            $table->foreignId('watch_party_member_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 8);               // key of WatchPartyReaction::KINDS, or "text"
            $table->string('body', 80)->nullable();  // text reactions only
            $table->unsignedInteger('position_s');   // seconds into the title when it was sent
            $table->timestamp('created_at')->useCurrent();

            $table->index(['watch_party_id', 'id']);
        });

        // Anonymous per-minute reaction totals across all Watch Alongs ("Envydeo moments" on title pages).
        Schema::create('title_moments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tmdb_id');
            $table->string('media_type', 5);
            $table->unsignedSmallInteger('minute');
            $table->string('kind', 8);
            $table->unsignedInteger('count')->default(0);

            $table->unique(['tmdb_id', 'media_type', 'minute', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('title_moments');
        Schema::dropIfExists('watch_party_reactions');
        Schema::dropIfExists('watch_party_members');
        Schema::dropIfExists('watch_parties');
    }
};
