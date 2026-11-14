<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReorderingRule extends Model
{
    use HasFactory;

    protected $table = 'reordering_rules';

    protected $fillable = [
        'rule_code',
        'branch_id',
        'branch_name',
        'product_id',
        'product_sku',
        'product_name',
        'category',
        'uom',
        'strategy',
        'min_stock',
        'max_stock',
        'reorder_point',
        'moq',
        'safety_buffer',
        'lead_time_days',
        'action_route',
        'preferred_vendor_id',
        'preferred_vendor_name',
        'source_warehouse_id',
        'source_warehouse_name',
        'target_warehouse_id',
        'is_active',
        'auto_generate_draft',
        'last_evaluated_at',
        'last_triggered_at',
        'last_order_qty',
        'notes',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'product_id' => 'integer',
        'preferred_vendor_id' => 'integer',
        'min_stock' => 'float',
        'max_stock' => 'float',
        'reorder_point' => 'float',
        'moq' => 'float',
        'safety_buffer' => 'float',
        'lead_time_days' => 'integer',
        'last_order_qty' => 'float',
        'is_active' => 'boolean',
        'auto_generate_draft' => 'boolean',
        'last_evaluated_at' => 'datetime',
        'last_triggered_at' => 'datetime',
    ];

    /**
     * Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByStrategy(Builder $query, string $strategy): Builder
    {
        return $query->where('strategy', $strategy);
    }

    /**
     * Strategy Label Accessor
     */
    public function getStrategyLabelAttribute(): string
    {
        return match ($this->strategy) {
            'min_max' => 'Min-Max (Order Point)',
            'commissary_push' => 'Central Commissary Push',
            'jit_back_to_back' => 'JIT / Demand Driven',
            default => ucfirst(str_replace('_', ' ', $this->strategy)),
        };
    }

    /**
     * Action Route Label Accessor
     */
    public function getActionRouteLabelAttribute(): string
    {
        return match ($this->action_route) {
            'purchase_order' => 'Purchase Order (Vendor)',
            'internal_transfer' => 'Internal Transfer (Commissary)',
            default => ucfirst(str_replace('_', ' ', $this->action_route)),
        };
    }

    /**
     * Mathematical Formula for Required Order Quantity:
     * Order Qty = ceil((Max - NetAvailable) / MOQ) * MOQ
     */
    public static function calculateOrderQuantity(float $netAvailable, float $minStock, float $maxStock, float $moq = 1.0): float
    {
        if ($netAvailable > $minStock) {
            return 0.00;
        }

        $deficit = max(0.00, $maxStock - $netAvailable);
        $moq = max(1.0, $moq);

        return ceil($deficit / $moq) * $moq;
    }
}

