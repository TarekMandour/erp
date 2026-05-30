<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    protected $table = 'inventory_transactions';

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'variant_id',
        'type',
        'quantity',
        'unit_cost',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity'  => 'decimal:3',
        'unit_cost' => 'decimal:2',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function getTypeNameAttribute(): string
    {
        return match ($this->type) {
            'in'         => 'وارد',
            'out'        => 'صادر',
            'adjustment' => 'تعديل',
            default      => $this->type,
        };
    }
}
