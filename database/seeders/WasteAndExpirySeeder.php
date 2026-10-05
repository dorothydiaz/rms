<?php

namespace Database\Seeders;

use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockLedger;
use App\Models\Inventory\WasteRecord;
use App\Models\Inventory\WasteRecordItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WasteAndExpirySeeder extends Seeder
{
    /**
     * Seed initial realistic waste, defect and expiry records.
     */
    public function run(): void
    {
        $dairy = InventoryItem::where('sku', 'RAW-DAIRY-001')->first()
            ?? InventoryItem::where('name', 'like', '%Milk%')->first()
            ?? InventoryItem::first();

        $meat = InventoryItem::where('sku', 'RAW-MEAT-001')->first()
            ?? InventoryItem::where('name', 'like', '%Beef%')->first()
            ?? InventoryItem::skip(1)->first()
            ?? $dairy;

        $produce = InventoryItem::where('category', 'like', '%Produce%')->first()
            ?? InventoryItem::skip(2)->first()
            ?? $dairy;

        if (!$dairy || !$meat) {
            return;
        }

        // 1. Spoilage Incident (Cold Storage Temperature Excursion)
        if (!WasteRecord::where('waste_number', 'WST-202610-0001')->exists()) {
            $record1 = WasteRecord::create([
                'waste_number' => 'WST-202610-0001',
                'waste_type' => 'SPOILAGE',
                'branch_name' => 'Central Commissary',
                'storage_location' => 'Cold Storage Walk-in #2',
                'total_cost' => (float) ($dairy->cost_price * 4.0),
                'total_items_count' => 1,
                'disposal_method' => 'Discarded / Trashed',
                'status' => 'APPROVED',
                'reported_by' => 'Chef Marco Rossi (Head Chef)',
                'approved_by' => 'David Vance (Operations Director)',
                'waste_date' => now()->subDays(2)->format('Y-m-d'),
                'notes' => 'Chiller compressor fault caused overnight temperature rise to 14°C. Milk spoiled.',
            ]);

            WasteRecordItem::create([
                'waste_record_id' => $record1->id,
                'inventory_item_id' => $dairy->id,
                'sku' => $dairy->sku,
                'item_name' => $dairy->name,
                'category' => $dairy->category,
                'uom' => $dairy->uom,
                'quantity' => 4.00,
                'unit_cost' => $dairy->cost_price,
                'total_cost' => (float) ($dairy->cost_price * 4.0),
                'reason_code' => 'Cold Chain Temperature Breach',
                'batch_lot_no' => 'LOT-DRY-20260920',
                'expiry_date' => now()->addDays(2)->format('Y-m-d'),
                'action_taken' => 'Disposed',
                'notes' => 'Sour odor and curdling detected on morning prep check.',
            ]);
        }

        // 2. Expired Batch (Past Shelf Life)
        if (!WasteRecord::where('waste_number', 'WST-202610-0002')->exists()) {
            $record2 = WasteRecord::create([
                'waste_number' => 'WST-202610-0002',
                'waste_type' => 'EXPIRED',
                'branch_name' => 'Central Commissary',
                'storage_location' => 'Dry Ingredients Room',
                'total_cost' => (float) ($meat->cost_price * 2.5),
                'total_items_count' => 1,
                'disposal_method' => 'Composted',
                'status' => 'APPROVED',
                'reported_by' => 'Elena Santos (Inventory Custodian)',
                'approved_by' => 'David Vance (Operations Director)',
                'waste_date' => now()->subDay()->format('Y-m-d'),
                'notes' => 'Routine FIFO audit discovered backroom pack past printed expiration date.',
            ]);

            WasteRecordItem::create([
                'waste_record_id' => $record2->id,
                'inventory_item_id' => $meat->id,
                'sku' => $meat->sku,
                'item_name' => $meat->name,
                'category' => $meat->category,
                'uom' => $meat->uom,
                'quantity' => 2.50,
                'unit_cost' => $meat->cost_price,
                'total_cost' => (float) ($meat->cost_price * 2.5),
                'reason_code' => 'Expired on Shelf',
                'batch_lot_no' => 'LOT-MEAT-20260915',
                'expiry_date' => now()->subDays(3)->format('Y-m-d'),
                'action_taken' => 'Bio-waste Bin',
                'notes' => 'Passed expiration 3 days ago. Removed from stock.',
            ]);
        }

        // 3. Handling Damage / Packaging Defect
        if ($produce && !WasteRecord::where('waste_number', 'WST-202610-0003')->exists()) {
            $record3 = WasteRecord::create([
                'waste_number' => 'WST-202610-0003',
                'waste_type' => 'DAMAGED',
                'branch_name' => 'Central Commissary',
                'storage_location' => 'Receiving Dock 1',
                'total_cost' => (float) ($produce->cost_price * 3.0),
                'total_items_count' => 1,
                'disposal_method' => 'Supplier Return / Credit Claim',
                'status' => 'PENDING_REVIEW',
                'reported_by' => 'Jonas Kyle (Receiving Clerk)',
                'approved_by' => null,
                'waste_date' => now()->format('Y-m-d'),
                'notes' => 'Crate dropped during pallet unloading. Physical crushing and seal breach.',
            ]);

            WasteRecordItem::create([
                'waste_record_id' => $record3->id,
                'inventory_item_id' => $produce->id,
                'sku' => $produce->sku,
                'item_name' => $produce->name,
                'category' => $produce->category,
                'uom' => $produce->uom,
                'quantity' => 3.00,
                'unit_cost' => $produce->cost_price,
                'total_cost' => (float) ($produce->cost_price * 3.0),
                'reason_code' => 'Physical Handling Damage',
                'batch_lot_no' => 'LOT-PRD-20261001',
                'expiry_date' => now()->addDays(5)->format('Y-m-d'),
                'action_taken' => 'Held for Vendor Credit Inspection',
                'notes' => 'Filed delivery claim with supplier.',
            ]);
        }
    }
}
