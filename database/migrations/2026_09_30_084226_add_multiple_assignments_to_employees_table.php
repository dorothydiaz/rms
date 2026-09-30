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
        Schema::table('employees', function (Blueprint $table) {
            $table->json('assigned_department_ids')->nullable()->after('department_id');
            $table->json('assigned_branch_ids')->nullable()->after('branch_id');
            $table->json('assigned_position_ids')->nullable()->after('position_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['assigned_department_ids', 'assigned_branch_ids', 'assigned_position_ids']);
        });
    }
};
