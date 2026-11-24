<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class SalesReturnItem extends Model
{
    protected $fillable = [
        'sales_return_id',
        'order_item_id',
        'product_id',
        'variant_id',
        'unit_conversion_id',
        'quantity',
        'unit_price',
        'unit_cost',
        'gross',
        'discount',
        'invoice_discount',
        'tax_rate',
        'tax',
        'total',
    ];

    protected $casts = [
        'quantity'         => 'decimal:3',
        'unit_price'       => 'decimal:2',
        'unit_cost'        => 'decimal:4',
        'gross'            => 'decimal:2',
        'discount'         => 'decimal:2',
        'invoice_discount' => 'decimal:2',
        'tax_rate'         => 'decimal:2',
        'tax'              => 'decimal:2',
        'total'            => 'decimal:2',
    ];

    public function salesReturn()
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function unitConversion()
    {
        return $this->belongsTo(UnitConversion::class);
    }
}
