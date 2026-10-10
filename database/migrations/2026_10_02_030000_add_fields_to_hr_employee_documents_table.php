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
        $tableName = Schema::hasTable('hr_employee_documents') ? 'hr_employee_documents' : 'employee_documents';

        if (Schema::hasTable($tableName)) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'category')) {
                    $table->string('category', 50)->nullable()->after('document_name');
                }
                if (!Schema::hasColumn($tableName, 'issue_date')) {
                    $table->date('issue_date')->nullable()->after('category');
                }
                if (!Schema::hasColumn($tableName, 'status')) {
                    $table->string('status', 30)->default('Pending')->after('expiry_date');
                }
                if (!Schema::hasColumn($tableName, 'is_verified')) {
                    $table->boolean('is_verified')->default(false)->after('status');
                }
                if (!Schema::hasColumn($tableName, 'verified_by')) {
                    $table->unsignedBigInteger('verified_by')->nullable()->after('is_verified');
                }
                if (!Schema::hasColumn($tableName, 'verified_at')) {
                    $table->timestamp('verified_at')->nullable()->after('verified_by');
                }
                if (!Schema::hasColumn($tableName, 'remarks')) {
                    $table->text('remarks')->nullable()->after('notes');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = Schema::hasTable('hr_employee_documents') ? 'hr_employee_documents' : 'employee_documents';

        if (Schema::hasTable($tableName)) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $columns = ['category', 'issue_date', 'status', 'is_verified', 'verified_by', 'verified_at', 'remarks'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
