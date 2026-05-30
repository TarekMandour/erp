<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariantPrice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'variant_id',
        'price',
        'sale_price',
        'sale_start',
        'sale_end',
        'quantity_prices',
        'customer_id',
        'customer_ids',
        'customer_group_id',
        'valid_from',
        'valid_to',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'quantity_prices' => 'array',
        'customer_ids'    => 'array',
        'is_active' => 'boolean',
        'allow_fractions' => 'boolean',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'sale_start' => 'date',
        'sale_end' => 'date',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
