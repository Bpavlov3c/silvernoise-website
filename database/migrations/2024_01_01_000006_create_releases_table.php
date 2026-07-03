<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('label_id')->constrained('labels')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();

            // Metadata
            $table->string('title');
            $table->string('title_version')->nullable();
            $table->string('catalog_id', 100)->nullable()->unique();
            $table->string('upc', 20)->nullable()->unique();

            // Status
            $table->enum('status', [
                'draft', 'pending', 'approved', 'delivered', 'live', 'takedown'
            ])->default('draft');

            // Dates
            $table->date('original_release_date')->nullable();

            // Copyright
            $table->string('copyright_c')->nullable();
            $table->string('copyright_p')->nullable();

            // Cover art
            $table->string('cover_art_url')->nullable();
            $table->string('cover_art_path')->nullable();

            // Distribution
            $table->boolean('physical_distribution')->default(false);

            // KVZ sync
            $table->string('kvz_id', 100)->nullable()->unique();
            $table->timestamp('kvz_synced_at')->nullable();
            $table->jsonb('kvz_raw')->nullable();

            $table->timestamps();

            $table->index('label_id');
            $table->index('customer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('releases');
    }
};
