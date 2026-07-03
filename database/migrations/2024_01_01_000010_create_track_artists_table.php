<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('track_artists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('track_id')->constrained('tracks')->cascadeOnDelete();
            $table->foreignId('artist_id')->constrained('artists')->restrictOnDelete();
            $table->string('role', 100)->default('Performer');
            // KVZ roles: Performer, Composer, Lyricist, Remixer, Featured
            $table->boolean('is_primary')->default(false);
            $table->smallInteger('sort_order')->default(0);

            $table->index('track_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('track_artists');
    }
};
