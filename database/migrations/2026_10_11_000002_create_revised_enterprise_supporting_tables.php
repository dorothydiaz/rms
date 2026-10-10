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
        Schema::disableForeignKeyConstraints();

        // 1. Warehouses
        if (!Schema::hasTable('warehouses')) {
            Schema::create('warehouses', function (Blueprint $table) {
                $table->uuid('warehouse_id')->primary();
                $table->string('warehouse_code', 50)->unique();
                $table->string('warehouse_name', 150);
                $table->string('warehouse_type', 50)->default('MAIN');
                $table->uuid('company_id')->nullable();
                $table->uuid('branch_id')->nullable();
                $table->string('warehouse_address_line_1', 150)->nullable();
                $table->string('warehouse_address_line_2', 150)->nullable();
                $table->string('warehouse_city', 100)->nullable();
                $table->string('warehouse_state_province_code', 50)->nullable();
                $table->string('warehouse_postal_code', 20)->nullable();
                $table->char('warehouse_country_code', 2)->default('PH');
                $table->string('warehouse_contact_person', 100)->nullable();
                $table->string('warehouse_phone_number', 30)->nullable();
                $table->string('warehouse_email_address', 255)->nullable();
                $table->boolean('is_temperature_controlled')->default(false);
                $table->boolean('is_transit_warehouse')->default(false);
                $table->boolean('warehouse_is_active')->default(true);
                $table->timestamp('warehouse_created_at')->nullable();
                $table->uuid('warehouse_created_by')->nullable();
                $table->timestamp('warehouse_updated_at')->nullable();
                $table->uuid('warehouse_updated_by')->nullable();
            });
        }

        // 2. Warehouse Locations (Bins / Zones)
        if (!Schema::hasTable('warehouse_locations')) {
            Schema::create('warehouse_locations', function (Blueprint $table) {
                $table->uuid('location_id')->primary();
                $table->uuid('warehouse_id')->index();
                $table->string('location_code', 50);
                $table->string('location_name', 100)->nullable();
                $table->string('zone', 50)->nullable();
                $table->string('aisle', 20)->nullable();
                $table->string('rack', 20)->nullable();
                $table->string('shelf', 20)->nullable();
                $table->string('bin', 20)->nullable();
                $table->string('location_type', 30)->default('STORAGE');
                $table->boolean('is_pickable')->default(true);
                $table->boolean('is_receipt_allowed')->default(true);
                $table->boolean('is_quarantine')->default(false);
                $table->decimal('max_weight_capacity', 18, 4)->nullable();
                $table->decimal('max_volume_capacity', 18, 4)->nullable();
                $table->boolean('location_is_active')->default(true);
                $table->timestamp('location_created_at')->nullable();
                $table->uuid('location_created_by')->nullable();
                $table->timestamp('location_updated_at')->nullable();
                $table->uuid('location_updated_by')->nullable();
            });
        }

        // 3. Lots (Batch Tracking)
        if (!Schema::hasTable('lots')) {
            Schema::create('lots', function (Blueprint $table) {
                $table->uuid('lot_id')->primary();
                $table->uuid('item_id')->index();
                $table->string('lot_number', 100);
                $table->string('vendor_lot_number', 100)->nullable();
                $table->date('manufacture_date')->nullable();
                $table->date('expiration_date')->nullable()->index();
                $table->string('lot_status', 30)->default('ACTIVE');
                $table->timestamp('lot_created_at')->nullable();
                $table->uuid('lot_created_by')->nullable();
                $table->timestamp('lot_updated_at')->nullable();
                $table->uuid('lot_updated_by')->nullable();
            });
        }

        // 4. Stock Balances
        if (!Schema::hasTable('stock_balances')) {
            Schema::create('stock_balances', function (Blueprint $table) {
                $table->uuid('stock_balance_id')->primary();
                $table->uuid('item_id')->index();
                $table->uuid('warehouse_id')->index();
                $table->uuid('location_id')->nullable()->index();
                $table->uuid('lot_id')->nullable()->index();
                $table->decimal('on_hand_quantity', 18, 4)->default(0.0000);
                $table->decimal('reserved_quantity', 18, 4)->default(0.0000);
                $table->decimal('available_quantity', 18, 4)->default(0.0000);
                $table->decimal('average_unit_cost', 18, 4)->default(0.0000);
                $table->timestamp('stock_balance_updated_at')->nullable();
            });
        }

        // 5. Vendor Addresses
        if (!Schema::hasTable('vendor_addresses')) {
            Schema::create('vendor_addresses', function (Blueprint $table) {
                $table->uuid('vendor_address_id')->primary();
                $table->uuid('vendor_id')->index();
                $table->string('address_type', 30)->default('HEADQUARTERS');
                $table->string('vendor_address_line_1', 150);
                $table->string('vendor_address_line_2', 150)->nullable();
                $table->string('vendor_address_city', 100)->nullable();
                $table->string('vendor_address_state_province_code', 50)->nullable();
                $table->string('vendor_address_postal_code', 20)->nullable();
                $table->char('vendor_address_country_code', 2)->default('PH');
                $table->boolean('is_primary_address')->default(true);
                $table->boolean('vendor_address_is_active')->default(true);
                $table->timestamp('vendor_address_created_at')->nullable();
                $table->uuid('vendor_address_created_by')->nullable();
                $table->timestamp('vendor_address_updated_at')->nullable();
                $table->uuid('vendor_address_updated_by')->nullable();
            });
        }

        // 6. Vendor Banks
        if (!Schema::hasTable('vendor_banks')) {
            Schema::create('vendor_banks', function (Blueprint $table) {
                $table->uuid('vendor_bank_id')->primary();
                $table->uuid('vendor_id')->index();
                $table->string('bank_name', 150);
                $table->string('bank_branch_name', 150)->nullable();
                $table->string('bank_account_name', 150);
                $table->string('bank_account_number', 50);
                $table->string('bank_swift_bic', 20)->nullable();
                $table->char('bank_currency_code', 3)->default('PHP');
                $table->boolean('is_primary_remittance')->default(true);
                $table->string('bank_verification_status', 30)->default('PENDING');
                $table->timestamp('bank_verified_at')->nullable();
                $table->uuid('bank_verified_by')->nullable();
                $table->boolean('vendor_bank_is_active')->default(true);
                $table->timestamp('vendor_bank_created_at')->nullable();
                $table->uuid('vendor_bank_created_by')->nullable();
                $table->timestamp('vendor_bank_updated_at')->nullable();
                $table->uuid('vendor_bank_updated_by')->nullable();
            });
        }

        // 7. Vendor Contacts
        if (!Schema::hasTable('vendor_contacts')) {
            Schema::create('vendor_contacts', function (Blueprint $table) {
                $table->uuid('vendor_contact_id')->primary();
                $table->uuid('vendor_id')->index();
                $table->string('contact_type', 50)->default('SALES');
                $table->string('first_name', 50);
                $table->string('last_name', 50)->nullable();
                $table->string('job_title', 100)->nullable();
                $table->string('vendor_contact_email_address', 255)->nullable();
                $table->string('vendor_contact_phone_number', 30)->nullable();
                $table->boolean('is_primary_contact')->default(true);
                $table->boolean('vendor_contact_is_active')->default(true);
                $table->timestamp('vendor_contact_created_at')->nullable();
                $table->uuid('vendor_contact_created_by')->nullable();
                $table->timestamp('vendor_contact_updated_at')->nullable();
                $table->uuid('vendor_contact_updated_by')->nullable();
            });
        }

        // 8. UoM Conversions
        if (!Schema::hasTable('uom_conversions')) {
            Schema::create('uom_conversions', function (Blueprint $table) {
                $table->uuid('uom_conversion_id')->primary();
                $table->uuid('item_id')->index();
                $table->string('from_uom_code', 20);
                $table->string('to_uom_code', 20);
                $table->decimal('conversion_rate', 18, 6);
                $table->boolean('uom_conversion_is_active')->default(true);
                $table->timestamp('uom_conversion_created_at')->nullable();
                $table->uuid('uom_conversion_created_by')->nullable();
                $table->timestamp('uom_conversion_updated_at')->nullable();
                $table->uuid('uom_conversion_updated_by')->nullable();
            });
        }

        // 9. Work Order Outputs
        if (!Schema::hasTable('work_order_outputs')) {
            Schema::create('work_order_outputs', function (Blueprint $table) {
                $table->uuid('work_order_output_id')->primary();
                $table->uuid('work_order_id')->index();
                $table->date('output_date');
                $table->decimal('output_quantity', 18, 4);
                $table->decimal('output_scrap_quantity', 18, 4)->default(0.0000);
                $table->string('base_uom_code', 20);
                $table->string('produced_lot_number', 100)->nullable();
                $table->string('produced_serial_number', 100)->nullable();
                $table->uuid('destination_location_id')->nullable();
                $table->decimal('output_unit_cost', 18, 4)->default(0.0000);
                $table->timestamp('work_order_output_created_at')->nullable();
                $table->uuid('work_order_output_created_by')->nullable();
                $table->timestamp('work_order_output_updated_at')->nullable();
                $table->uuid('work_order_output_updated_by')->nullable();
            });
        }

        // 10. Stock Adjustment Header & Lines
        if (!Schema::hasTable('stock_adjustment_header')) {
            Schema::create('stock_adjustment_header', function (Blueprint $table) {
                $table->uuid('adjustment_id')->primary();
                $table->string('adjustment_number', 50)->unique();
                $table->string('adjustment_type', 30)->default('CYCLE_COUNT');
                $table->uuid('adjustment_warehouse_id')->nullable()->index();
                $table->date('adjustment_date');
                $table->string('adjustment_reason_code', 50)->nullable();
                $table->string('adjustment_status', 30)->default('DRAFT');
                $table->text('adjustment_notes')->nullable();
                $table->string('adjustment_approval_status', 30)->default('PENDING');
                $table->uuid('approval_request_id')->nullable();
                $table->timestamp('adjustment_posted_at')->nullable();
                $table->uuid('adjustment_posted_by')->nullable();
                $table->timestamp('adjustment_created_at')->nullable();
                $table->uuid('adjustment_created_by')->nullable();
                $table->timestamp('adjustment_updated_at')->nullable();
                $table->uuid('adjustment_updated_by')->nullable();
            });
        }

        if (!Schema::hasTable('stock_adjustment_lines')) {
            Schema::create('stock_adjustment_lines', function (Blueprint $table) {
                $table->uuid('adjustment_line_id')->primary();
                $table->uuid('adjustment_id')->index();
                $table->smallInteger('line_number')->default(1);
                $table->uuid('item_id')->index();
                $table->decimal('system_quantity', 18, 4)->default(0.0000);
                $table->decimal('counted_quantity', 18, 4)->default(0.0000);
                $table->decimal('adjustment_quantity', 18, 4)->default(0.0000);
                $table->string('base_uom_code', 20);
                $table->decimal('adjustment_unit_cost', 18, 4)->default(0.0000);
                $table->decimal('total_adjustment_value', 18, 4)->default(0.0000);
                $table->string('lot_number', 100)->nullable();
                $table->string('serial_number', 100)->nullable();
                $table->uuid('adjustment_location_id')->nullable();
                $table->string('gl_adjustment_account_id', 50)->nullable();
                $table->timestamp('adjustment_line_created_at')->nullable();
                $table->uuid('adjustment_line_created_by')->nullable();
                $table->timestamp('adjustment_line_updated_at')->nullable();
                $table->uuid('adjustment_line_updated_by')->nullable();
            });
        }

        // 11. RFQ Header & Lines
        if (!Schema::hasTable('rfq_header')) {
            Schema::create('rfq_header', function (Blueprint $table) {
                $table->uuid('rfq_id')->primary();
                $table->string('rfq_number', 50)->unique();
                $table->uuid('company_id')->nullable();
                $table->uuid('branch_id')->nullable();
                $table->uuid('ship_to_warehouse_id')->nullable();
                $table->uuid('vendor_id')->nullable()->index();
                $table->uuid('vendor_contact_id')->nullable();
                $table->date('rfq_date');
                $table->date('submission_deadline')->nullable();
                $table->date('quote_valid_until_date')->nullable();
                $table->date('rfq_requested_delivery_date')->nullable();
                $table->char('rfq_currency_code', 3)->default('PHP');
                $table->string('rfq_payment_terms_code', 30)->default('NET30');
                $table->char('rfq_incoterms_code', 3)->default('FOB');
                $table->decimal('rfq_estimated_total_amount', 18, 4)->default(0.0000);
                $table->decimal('rfq_quoted_total_amount', 18, 4)->default(0.0000);
                $table->string('rfq_status', 30)->default('DRAFT');
                $table->text('rfq_notes')->nullable();
                $table->string('rfq_approval_status', 30)->default('PENDING');
                $table->uuid('approval_request_id')->nullable();
                $table->string('rfq_award_approval_status', 30)->default('NOT_REQUIRED');
                $table->uuid('award_approval_request_id')->nullable();
                $table->timestamp('rfq_created_at')->nullable();
                $table->uuid('rfq_created_by')->nullable();
                $table->timestamp('rfq_updated_at')->nullable();
                $table->uuid('rfq_updated_by')->nullable();
            });
        }

        if (!Schema::hasTable('rfq_lines')) {
            Schema::create('rfq_lines', function (Blueprint $table) {
                $table->uuid('rfq_line_id')->primary();
                $table->uuid('rfq_id')->index();
                $table->smallInteger('line_number')->default(1);
                $table->uuid('item_id')->index();
                $table->decimal('requested_quantity', 18, 4);
                $table->string('purchase_uom_code', 20);
                $table->decimal('target_unit_price', 18, 4)->default(0.0000);
                $table->decimal('quoted_unit_price', 18, 4)->default(0.0000);
                $table->decimal('quoted_line_total', 18, 4)->default(0.0000);
                $table->integer('lead_time_days')->default(0);
                $table->boolean('is_awarded')->default(false);
                $table->text('rfq_line_notes')->nullable();
                $table->timestamp('rfq_line_created_at')->nullable();
                $table->uuid('rfq_line_created_by')->nullable();
                $table->timestamp('rfq_line_updated_at')->nullable();
                $table->uuid('rfq_line_updated_by')->nullable();
            });
        }

        // 12. Vendor Bills Header & Lines (AP 3-Way Match)
        if (!Schema::hasTable('vendor_bill_header')) {
            Schema::create('vendor_bill_header', function (Blueprint $table) {
                $table->uuid('vendor_bill_id')->primary();
                $table->string('vendor_bill_number', 50)->unique();
                $table->string('vendor_invoice_number', 100);
                $table->uuid('company_id')->nullable();
                $table->uuid('branch_id')->nullable();
                $table->uuid('vendor_id')->index();
                $table->uuid('vendor_bank_id')->nullable();
                $table->uuid('purchase_order_id')->nullable()->index();
                $table->date('vendor_invoice_date');
                $table->date('bill_received_date');
                $table->date('bill_due_date');
                $table->string('bill_payment_terms_code', 30)->default('NET30');
                $table->char('bill_currency_code', 3)->default('PHP');
                $table->decimal('bill_exchange_rate', 12, 6)->default(1.000000);
                $table->decimal('bill_subtotal_amount', 18, 4)->default(0.0000);
                $table->decimal('bill_tax_amount', 18, 4)->default(0.0000);
                $table->decimal('bill_shipping_amount', 18, 4)->default(0.0000);
                $table->decimal('bill_total_amount', 18, 4)->default(0.0000);
                $table->decimal('bill_paid_amount', 18, 4)->default(0.0000);
                $table->string('bill_match_status', 30)->default('UNMATCHED');
                $table->string('vendor_bill_status', 30)->default('DRAFT');
                $table->string('vendor_bill_approval_status', 30)->default('PENDING');
                $table->uuid('approval_request_id')->nullable();
                $table->timestamp('vendor_bill_posted_at')->nullable();
                $table->uuid('vendor_bill_posted_by')->nullable();
                $table->text('vendor_bill_notes')->nullable();
                $table->timestamp('vendor_bill_created_at')->nullable();
                $table->uuid('vendor_bill_created_by')->nullable();
                $table->timestamp('vendor_bill_updated_at')->nullable();
                $table->uuid('vendor_bill_updated_by')->nullable();
            });
        }

        if (!Schema::hasTable('vendor_bill_lines')) {
            Schema::create('vendor_bill_lines', function (Blueprint $table) {
                $table->uuid('vendor_bill_line_id')->primary();
                $table->uuid('vendor_bill_id')->index();
                $table->smallInteger('line_number')->default(1);
                $table->uuid('item_id')->index();
                $table->uuid('po_line_id')->nullable();
                $table->uuid('stock_in_line_id')->nullable();
                $table->string('bill_line_description', 255)->nullable();
                $table->decimal('bill_quantity', 18, 4);
                $table->string('purchase_uom_code', 20);
                $table->decimal('bill_unit_price', 18, 4);
                $table->string('tax_code', 30)->default('VAT12');
                $table->decimal('bill_line_tax_amount', 18, 4)->default(0.0000);
                $table->decimal('bill_line_subtotal', 18, 4)->default(0.0000);
                $table->decimal('bill_line_total', 18, 4)->default(0.0000);
                $table->string('gl_expense_account_id', 50)->nullable();
                $table->text('vendor_bill_line_notes')->nullable();
                $table->timestamp('vendor_bill_line_created_at')->nullable();
                $table->uuid('vendor_bill_line_created_by')->nullable();
                $table->timestamp('vendor_bill_line_updated_at')->nullable();
                $table->uuid('vendor_bill_line_updated_by')->nullable();
            });
        }

        // 13. Universal Approval Engine Tables
        if (!Schema::hasTable('approval_workflows')) {
            Schema::create('approval_workflows', function (Blueprint $table) {
                $table->uuid('approval_workflow_id')->primary();
                $table->string('approval_workflow_code', 50)->unique();
                $table->string('approval_workflow_name', 150);
                $table->string('approval_document_type', 40); // PO, STOCK_IN, VENDOR_BILL, etc.
                $table->uuid('company_id')->nullable();
                $table->uuid('branch_id')->nullable();
                $table->decimal('approval_min_amount', 18, 4)->default(0.0000);
                $table->decimal('approval_max_amount', 18, 4)->nullable();
                $table->char('approval_currency_code', 3)->default('PHP');
                $table->json('approval_condition')->nullable();
                $table->smallInteger('approval_priority')->default(1);
                $table->date('approval_effective_from')->nullable();
                $table->date('approval_effective_to')->nullable();
                $table->boolean('approval_workflow_is_active')->default(true);
                $table->timestamp('approval_workflow_created_at')->nullable();
                $table->uuid('approval_workflow_created_by')->nullable();
                $table->timestamp('approval_workflow_updated_at')->nullable();
                $table->uuid('approval_workflow_updated_by')->nullable();
            });
        }

        if (!Schema::hasTable('approval_workflow_steps')) {
            Schema::create('approval_workflow_steps', function (Blueprint $table) {
                $table->uuid('approval_step_id')->primary();
                $table->uuid('approval_workflow_id')->index();
                $table->smallInteger('approval_step_number')->default(1);
                $table->string('approval_step_name', 100);
                $table->string('approver_type', 20)->default('ROLE');
                $table->uuid('approver_role_id')->nullable();
                $table->uuid('approver_user_id')->nullable();
                $table->uuid('approver_position_id')->nullable();
                $table->string('approval_mode', 20)->default('ANY_ONE');
                $table->smallInteger('approval_min_approvals')->default(1);
                $table->boolean('is_self_approval_allowed')->default(false);
                $table->integer('approval_sla_hours')->default(24);
                $table->uuid('escalation_user_id')->nullable();
                $table->boolean('approval_step_is_active')->default(true);
                $table->timestamp('approval_step_created_at')->nullable();
                $table->uuid('approval_step_created_by')->nullable();
                $table->timestamp('approval_step_updated_at')->nullable();
                $table->uuid('approval_step_updated_by')->nullable();
            });
        }

        if (!Schema::hasTable('approval_requests')) {
            Schema::create('approval_requests', function (Blueprint $table) {
                $table->uuid('approval_request_id')->primary();
                $table->uuid('approval_workflow_id')->index();
                $table->string('approval_document_type', 40);
                $table->string('approval_document_id', 50)->index();
                $table->string('approval_document_number', 50);
                $table->uuid('company_id')->nullable();
                $table->uuid('branch_id')->nullable();
                $table->decimal('approval_amount', 18, 4)->default(0.0000);
                $table->char('approval_currency_code', 3)->default('PHP');
                $table->smallInteger('approval_submission_number')->default(1);
                $table->smallInteger('approval_current_step_number')->default(1);
                $table->string('approval_request_status', 30)->default('PENDING');
                $table->timestamp('approval_completed_at')->nullable();
                $table->uuid('approval_final_approver_id')->nullable();
                $table->timestamp('approval_request_created_at')->nullable();
                $table->uuid('approval_request_created_by')->nullable();
                $table->timestamp('approval_request_updated_at')->nullable();
                $table->uuid('approval_request_updated_by')->nullable();
            });
        }

        if (!Schema::hasTable('approval_actions')) {
            Schema::create('approval_actions', function (Blueprint $table) {
                $table->uuid('approval_action_id')->primary();
                $table->uuid('approval_request_id')->index();
                $table->uuid('approval_step_id')->nullable();
                $table->uuid('acted_by_user_id')->nullable();
                $table->string('approval_action_type', 20); // APPROVED, REJECTED, RETURNED, ESCALATED
                $table->timestamp('approval_action_at')->nullable();
                $table->text('approval_action_comments')->nullable();
                $table->uuid('delegated_to_user_id')->nullable();
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('approval_actions');
        Schema::dropIfExists('approval_requests');
        Schema::dropIfExists('approval_workflow_steps');
        Schema::dropIfExists('approval_workflows');
        Schema::dropIfExists('vendor_bill_lines');
        Schema::dropIfExists('vendor_bill_header');
        Schema::dropIfExists('rfq_lines');
        Schema::dropIfExists('rfq_header');
        Schema::dropIfExists('stock_adjustment_lines');
        Schema::dropIfExists('stock_adjustment_header');
        Schema::dropIfExists('work_order_outputs');
        Schema::dropIfExists('uom_conversions');
        Schema::dropIfExists('vendor_contacts');
        Schema::dropIfExists('vendor_banks');
        Schema::dropIfExists('vendor_addresses');
        Schema::dropIfExists('stock_balances');
        Schema::dropIfExists('lots');
        Schema::dropIfExists('warehouse_locations');
        Schema::dropIfExists('warehouses');

        Schema::enableForeignKeyConstraints();
    }
};
