<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 50)->default('kvz');
            $table->string('endpoint');
            $table->char('method', 6)->default('GET');
            $table->smallInteger('status_code')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->integer('records_synced')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('source');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
