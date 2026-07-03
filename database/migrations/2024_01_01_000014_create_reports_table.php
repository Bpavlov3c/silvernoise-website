<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('label_id')->constrained('labels')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();

            // Display
            $table->string('name');
            $table->string('period_label', 100);
            $table->date('period_start');
            $table->date('period_end');
            $table->date('report_date');

            // File (stored in Cloudflare R2)
            $table->string('file_path');
            $table->string('file_url');

            // Financials
            $table->decimal('total_earnings', 12, 4)->default(0);
            $table->char('currency', 3)->default('EUR');

            // Payment status
            $table->enum('status', ['unpaid', 'payment_requested', 'paid'])->default('unpaid');
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index('label_id');
            $table->index('customer_id');
            $table->index('status');
            $table->index(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
