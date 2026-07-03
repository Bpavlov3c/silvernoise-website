<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();

            // Amount
            $table->decimal('amount', 12, 4);
            $table->char('currency', 3)->default('EUR');

            // IBAN bank transfer details (captured at request time)
            $table->string('iban', 50);
            $table->string('bank_name')->nullable();
            $table->string('account_holder')->nullable();

            // Invoice
            $table->string('invoice_path')->nullable();
            $table->string('invoice_url')->nullable();

            // Status
            $table->enum('status', [
                'pending', 'processing', 'sent', 'completed', 'rejected'
            ])->default('pending');
            $table->text('admin_notes')->nullable();

            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_requests');
    }
};
