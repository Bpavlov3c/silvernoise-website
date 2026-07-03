<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_labels', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('label_id')->constrained('labels')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamp('assigned_at')->useCurrent();

            $table->primary(['customer_id', 'label_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_labels');
    }
};
