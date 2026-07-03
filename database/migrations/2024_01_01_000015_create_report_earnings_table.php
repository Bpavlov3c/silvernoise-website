<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->string('platform', 100);
            $table->char('country_code', 2)->nullable();
            $table->foreignId('track_id')->nullable()->constrained('tracks')->nullOnDelete();
            $table->bigInteger('streams')->default(0);
            $table->decimal('earnings', 12, 4)->default(0);
            $table->char('currency', 3)->default('EUR');

            $table->index('report_id');
            $table->index('platform');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_earnings');
    }
};
