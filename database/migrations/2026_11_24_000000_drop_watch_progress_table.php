<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Watch progress was only ever recorded by the embedded third-party player,
 * which has been removed, so the table is no longer used.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('watch_progress');
    }

    public function down(): void
    {
        Schema::create('watch_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->integer('tmdb_id');
            $table->string('media_type');
            $table->float('watched');
            $table->float('duration');
            $table->timestamps();
        });
    }
};
