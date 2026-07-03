<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('surname', 100);
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['seller', 'admin', 'finance'])->default('seller');
            $table->enum('customer_type', ['individual', 'company'])->nullable();
            $table->string('company_name')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->timestamp('contract_terminated_at')->nullable();
            $table->boolean('featured')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('activation_token')->nullable()->unique();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
