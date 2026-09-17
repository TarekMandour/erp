<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class WarehouseTransaction extends Model
{
    protected $table = 'warehouse_transactions';

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'variant_id',
        'unit_id',
        'unit_conversion_id',
        'type',
        'quantity',
        'unit_cost',
        'balance_after',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity'      => 'decimal:3',
        'unit_cost'     => 'decimal:2',
        'balance_after' => 'decimal:3',
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

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function unitConversion()
    {
        return $this->belongsTo(UnitConversion::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\Admin::class, 'created_by');
    }

    public function getTypeNameAttribute(): string
    {
        return match ($this->type) {
            'in'           => 'وارد',
            'out'          => 'صادر',
            'transfer_in'  => 'تحويل وارد',
            'transfer_out' => 'تحويل صادر',
            'adjustment'   => 'تعديل',
            default        => $this->type,
        };
    }

    public function getTypeBadgeAttribute(): string
    {
        return match ($this->type) {
            'in', 'transfer_in'   => 'badge-light-success',
            'out', 'transfer_out' => 'badge-light-danger',
            'adjustment'          => 'badge-light-warning',
            default               => 'badge-light-secondary',
        };
    }

    /**
     * تسجيل حركة مخزنية وتحديث رصيد المخزون الحالي في نفس العملية
     * تُستخدم فقط عندما لم يتم تعديل InventoryItem مسبقاً (مثل الإضافة اليدوية)
     */
    public static function record(array $data): self
    {
        $inventory = InventoryItem::firstOrCreate(
            [
                'warehouse_id' => $data['warehouse_id'],
                'product_id'   => $data['product_id'],
                'variant_id'   => $data['variant_id'] ?? null,
            ],
            ['quantity' => 0]
        );

        $qty   = (float) $data['quantity'];
        $isIn  = in_array($data['type'], ['in', 'transfer_in'], true);
        $delta = $isIn ? $qty : -$qty;

        $inventory->quantity = max(0, (float) $inventory->quantity + $delta);
        $inventory->save();

        $data['balance_after'] = $inventory->quantity;
        $data['created_by']    = $data['created_by'] ?? auth()->id();
        $data['unit_id']       = $data['unit_id'] ?? Product::find($data['product_id'])?->unit_id;

        return static::create($data);
    }

    /**
     * تسجيل حركة كسجل تدقيق فقط، بدون تعديل InventoryItem
     * (تُستخدم عندما يكون رصيد المخزون قد عُدّل بالفعل بواسطة الاستدعاء الأصلي)
     */
    public static function log(array $data): self
    {
        $inventory = InventoryItem::where([
            'warehouse_id' => $data['warehouse_id'],
            'product_id'   => $data['product_id'],
            'variant_id'   => $data['variant_id'] ?? null,
        ])->first();

        $data['balance_after'] = $inventory ? (float) $inventory->quantity : 0;
        $data['created_by']    = $data['created_by'] ?? auth()->id();
        $data['unit_id']       = $data['unit_id'] ?? Product::find($data['product_id'])?->unit_id;

        return static::create($data);
    }
}
