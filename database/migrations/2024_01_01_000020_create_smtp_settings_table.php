<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smtp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('host');
            $table->smallInteger('port')->default(587);
            $table->string('username');
            $table->string('password_enc'); // encrypted via Laravel Crypt
            $table->enum('encryption', ['tls', 'ssl', 'none'])->default('tls');
            $table->string('from_email');
            $table->string('from_name')->default('Silvernoise');
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smtp_settings');
    }
};
