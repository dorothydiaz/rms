<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UnitOfMeasure extends Model
{
    protected $table = 'units_of_measure';
    protected $primaryKey = 'uom_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false; // Using custom timestamp columns

    protected $fillable = [
        'uom_id',
        'uom_code',
        'uom_name',
        'uom_category',
        'uom_type',
        'base_ratio',
        'is_base_unit',
        'rounding_precision',
        'uom_is_active',
        'uom_created_at',
        'uom_created_by',
        'uom_updated_at',
        'uom_updated_by',
    ];

    protected $casts = [
        'base_ratio' => 'decimal:6',
        'is_base_unit' => 'boolean',
        'rounding_precision' => 'decimal:4',
        'uom_is_active' => 'boolean',
        'uom_created_at' => 'datetime',
        'uom_updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uom_id)) {
                $model->uom_id = (string) Str::uuid();
            }
            if (empty($model->uom_created_at)) {
                $model->uom_created_at = now();
            }
            if (empty($model->uom_updated_at)) {
                $model->uom_updated_at = now();
            }
        });

        static::updating(function ($model) {
            $model->uom_updated_at = now();
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(UomCategory::class, 'uom_category', 'category_code');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('uom_is_active', true);
    }

    /**
     * Convert given quantity in this UOM to the base unit
     */
    public function convertToBase(float $qty): float
    {
        return $qty * (float) $this->base_ratio;
    }

    /**
     * Convert quantity from base unit to this UOM
     */
    public function convertFromBase(float $baseQty): float
    {
        $ratio = (float) $this->base_ratio;
        if ($ratio == 0) return 0;
        return $baseQty / $ratio;
    }
}
