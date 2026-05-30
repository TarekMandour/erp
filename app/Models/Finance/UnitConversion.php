<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitConversion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'variant_id',
        'base_unit',
        'target_unit',
        'conversion_rate',
        'is_default',
        'allow_fractions',
        'decimal_places',
    ];

    protected $casts = [
        'conversion_rate' => 'decimal:4',
        'is_default' => 'boolean',
        'allow_fractions' => 'boolean',
        'decimal_places' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
