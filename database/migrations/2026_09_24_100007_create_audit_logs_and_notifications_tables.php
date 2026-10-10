<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Audit Logs (Financial, attendance, leave, employee data changes)
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 50); // Login, Logout, Create, Update, Delete, Approve, Reject, Finalize, Calculate, Adjust
                $table->string('module', 50); // Employees, Attendance, Leave, Payroll, Performance, Training, Settings, RBAC
                $table->string('record_id', 50)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->json('previous_value')->nullable();
                $table->json('new_value')->nullable();
                $table->text('details')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // Internal Administrative Notifications
        if (!Schema::hasTable('internal_notifications')) {
            Schema::create('internal_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('role_target', 50)->nullable(); // Super Admin, HR/Admin, Manager
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('title', 150);
                $table->text('message');
                $table->string('type', 50)->default('general'); // leave, attendance, overtime, payroll, birthday, regularization
                $table->string('link', 255)->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_notifications');
        Schema::dropIfExists('audit_logs');
    }
};
