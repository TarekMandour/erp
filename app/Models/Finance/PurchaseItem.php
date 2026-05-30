<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'product_id',
        'variant_id',
        'quantity',
        'unit_cost',
        'discount',
        'tax_rate',
        'total',
    ];

    protected $casts = [
        'quantity'  => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'discount'  => 'decimal:2',
        'tax_rate'  => 'decimal:2',
        'total'     => 'decimal:2',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
