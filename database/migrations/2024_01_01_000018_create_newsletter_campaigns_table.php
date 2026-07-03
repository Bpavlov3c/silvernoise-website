<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('subject_bg');
            $table->string('subject_en');
            $table->text('body_bg');
            $table->text('body_en');
            $table->enum('segment', [
                'all', 'active', 'inactive', 'artists', 'labels'
            ])->default('all');
            $table->enum('status', [
                'draft', 'scheduled', 'sending', 'sent'
            ])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->integer('recipients_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_campaigns');
    }
};
