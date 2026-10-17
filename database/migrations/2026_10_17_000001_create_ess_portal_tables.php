<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ess_requests')) {
            Schema::create('ess_requests', function (Blueprint $table) {
                $table->id();
                $table->string('request_number', 50)->unique();
                $table->string('employee_id', 50)->index();
                $table->string('request_type', 50); // OVERTIME, OFFICIAL_BUSINESS, ATTENDANCE_CORRECTION, SHIFT_SWAP, UNDERTIME, PROFILE_UPDATE
                $table->json('details')->nullable();
                $table->text('reason')->nullable();
                $table->string('attachment_url')->nullable();
                $table->string('status', 30)->default('PENDING'); // DRAFT, PENDING, APPROVED, REJECTED, CANCELLED
                $table->unsignedSmallInteger('approval_step')->default(1);
                $table->unsignedBigInteger('approver_id')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('ess_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('ess_requests', 'details')) {
                    $table->json('details')->nullable();
                }
                if (!Schema::hasColumn('ess_requests', 'attachment_url')) {
                    $table->string('attachment_url')->nullable();
                }
                if (!Schema::hasColumn('ess_requests', 'approval_step')) {
                    $table->unsignedSmallInteger('approval_step')->default(1);
                }
                if (!Schema::hasColumn('ess_requests', 'approver_id')) {
                    $table->unsignedBigInteger('approver_id')->nullable();
                }
            });
        }

        if (!Schema::hasTable('ess_helpdesk_tickets')) {
            Schema::create('ess_helpdesk_tickets', function (Blueprint $table) {
                $table->id();
                $table->string('ticket_number', 50)->unique();
                $table->string('employee_id', 50)->index();
                $table->string('category', 60);
                $table->string('subject', 200);
                $table->string('priority', 20)->default('Normal'); // Low, Normal, High, Urgent
                $table->string('status', 30)->default('OPEN'); // OPEN, IN_PROGRESS, RESOLVED, CLOSED
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ess_helpdesk_messages')) {
            Schema::create('ess_helpdesk_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ticket_id')->index();
                $table->unsignedBigInteger('user_id');
                $table->text('message');
                $table->string('attachment_url')->nullable();
                $table->timestamps();

                $table->foreign('ticket_id')->references('id')->on('ess_helpdesk_tickets')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('ess_announcements')) {
            Schema::create('ess_announcements', function (Blueprint $table) {
                $table->id();
                $table->string('title', 200);
                $table->text('content');
                $table->string('category', 50)->default('General'); // General, Holiday, Payroll, HR Policy
                $table->string('priority', 20)->default('Normal'); // Low, Normal, High
                $table->boolean('is_active')->default(true);
                $table->timestamp('published_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ess_announcements');
        Schema::dropIfExists('ess_helpdesk_messages');
        Schema::dropIfExists('ess_helpdesk_tickets');
        Schema::dropIfExists('ess_requests');
    }
};
