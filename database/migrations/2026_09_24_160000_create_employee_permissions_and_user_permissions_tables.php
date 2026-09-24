<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_permissions')) {
            Schema::create('employee_permissions', function (Blueprint $table) {
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
                $table->primary(['employee_id', 'permission_id']);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('permission_user')) {
            Schema::create('permission_user', function (Blueprint $table) {
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
                $table->primary(['user_id', 'permission_id']);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_user');
        Schema::dropIfExists('employee_permissions');
    }
};
