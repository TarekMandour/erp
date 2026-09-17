<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class InventoryTransfer extends Model
{
    protected $table = 'inventory_transfers';

    protected $fillable = [
        'reference',
        'from_warehouse_id',
        'to_warehouse_id',
        'product_id',
        'variant_id',
        'unit_id',
        'unit_conversion_id',
        'quantity',
        'notes',
        'status',
        'completed_at',
        'cancelled_at',
        'created_by',
    ];

    protected $casts = [
        'quantity'     => 'decimal:3',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function unitConversion()
    {
        return $this->belongsTo(UnitConversion::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'completed' => '<span class="badge badge-light-success">مكتمل</span>',
            'cancelled' => '<span class="badge badge-light-danger">ملغي</span>',
            default     => '<span class="badge badge-light-warning">معلق</span>',
        };
    }

    /**
     * Generate next sequential reference like TRF-000001
     */
    public static function generateReference(): string
    {
        $last = static::orderByDesc('id')->lockForUpdate()->first();
        $next = $last ? ((int) ltrim(substr($last->reference, 4), '0') ?: 0) + 1 : 1;
        return 'TRF-' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
