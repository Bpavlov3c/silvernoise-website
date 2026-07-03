<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('release_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('release_id')->constrained('releases')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->enum('status', ['pending', 'delivered', 'live', 'takedown'])->default('pending');
            $table->string('store_release_url')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('live_at')->nullable();

            $table->unique(['release_id', 'store_id']);
            $table->index('release_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('release_stores');
    }
};
