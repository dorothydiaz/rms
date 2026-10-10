<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('login_attempts')) {
            Schema::create('login_attempts', function (Blueprint $table) {
                $table->id();
                $table->string('ip_address', 45);
                $table->string('identity', 100);
                $table->timestamp('attempted_at')->useCurrent();
                $table->boolean('is_successful')->default(false);
                $table->index(['ip_address', 'attempted_at'], 'idx_ip_attempted');
                $table->index(['identity', 'attempted_at'], 'idx_identity_attempted');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
