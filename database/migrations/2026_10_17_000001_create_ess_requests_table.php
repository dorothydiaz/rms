<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('ess_requests')) {
            Schema::create('ess_requests', function (Blueprint $table) {
                $table->id();
                $table->string('request_number', 50)->unique();
                $table->string('employee_id', 50)->index();
                $table->string('request_type', 40); // OVERTIME, OFFICIAL_BUSINESS, ATTENDANCE_CORRECTION, SHIFT_SWAP, SCHEDULE_ADJUSTMENT, UNDERTIME, PROFILE_UPDATE, COE_REQUEST
                $table->string('status', 30)->default('PENDING_APPROVAL'); // DRAFT, SUBMITTED, PENDING_APPROVAL, APPROVED, REJECTED, CANCELLED
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->decimal('total_hours', 8, 2)->nullable();
                $table->text('reason');
                $table->json('metadata')->nullable();
                $table->string('attachment_path', 255)->nullable();
                $table->unsignedBigInteger('current_approver_id')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();

                $table->foreign('current_approver_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('ess_request_logs')) {
            Schema::create('ess_request_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ess_request_id');
                $table->string('action', 50); // SUBMITTED, FORWARDED, APPROVED, REJECTED, CANCELLED
                $table->unsignedBigInteger('acted_by')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->foreign('ess_request_id')->references('id')->on('ess_requests')->onDelete('cascade');
                $table->foreign('acted_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ess_request_logs');
        Schema::dropIfExists('ess_requests');
    }
};
