<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class UomCategory extends Model
{
    protected $table = 'uom_categories';
    protected $primaryKey = 'category_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false; // Using custom timestamp columns

    protected $fillable = [
        'category_id',
        'category_code',
        'category_name',
        'category_description',
        'base_uom_code',
        'category_is_active',
        'category_created_at',
        'category_created_by',
        'category_updated_at',
        'category_updated_by',
    ];

    protected $casts = [
        'category_is_active' => 'boolean',
        'category_created_at' => 'datetime',
        'category_updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->category_id)) {
                $model->category_id = (string) Str::uuid();
            }
            if (empty($model->category_created_at)) {
                $model->category_created_at = now();
            }
            if (empty($model->category_updated_at)) {
                $model->category_updated_at = now();
            }
        });

        static::updating(function ($model) {
            $model->category_updated_at = now();
        });
    }

    public function units(): HasMany
    {
        return $this->hasMany(UnitOfMeasure::class, 'uom_category', 'category_code');
    }

    public function activeUnits(): HasMany
    {
        return $this->hasMany(UnitOfMeasure::class, 'uom_category', 'category_code')
            ->where('uom_is_active', true)
            ->orderBy('base_ratio', 'asc');
    }
}
