<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('template_key', 100)->nullable();
            $table->foreignId('campaign_id')->nullable()->constrained('newsletter_campaigns')->nullOnDelete();
            $table->string('to_email');
            $table->string('subject');
            $table->enum('status', ['sent', 'failed', 'bounced'])->default('sent');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->useCurrent();

            $table->index('user_id');
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
