<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SalesBundle extends Model
{
    protected $table = 'sales_bundles';
    protected $primaryKey = 'bundle_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'bundle_id',
        'company_id',
        'bundle_code',
        'bundle_name',
        'description',
        'bundle_price',
        'tax_code',
        'valid_from',
        'valid_until',
        'duration_mode',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'bundle_price' => 'decimal:4',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SalesBundle $b) {
            if (empty($b->bundle_id)) {
                $b->bundle_id = (string) Str::uuid();
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesBundleItem::class, 'bundle_id', 'bundle_id');
    }
}
