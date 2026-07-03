<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('release_id')->constrained('releases')->cascadeOnDelete();

            // Position
            $table->smallInteger('disc_number')->default(1);
            $table->smallInteger('track_number');

            // Metadata
            $table->string('title');
            $table->string('title_version')->nullable();
            $table->string('isrc', 20)->nullable()->unique();
            $table->string('audio_language', 10)->nullable();
            $table->integer('length')->nullable()->comment('Duration in seconds');
            $table->integer('preview_start')->nullable()->comment('Preview start in seconds');
            $table->boolean('explicit_lyrics')->default(false);

            // Rights
            $table->string('publisher')->nullable();
            $table->decimal('hfa_percentage', 5, 2)->nullable();
            $table->string('copyright_c')->nullable();
            $table->string('copyright_p')->nullable();

            // Audio file (stored in Cloudflare R2)
            $table->string('audio_file_path')->nullable();
            $table->bigInteger('audio_file_size')->nullable()->comment('Size in bytes');

            $table->timestamps();

            $table->index('release_id');
            $table->unique(['release_id', 'disc_number', 'track_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracks');
    }
};
