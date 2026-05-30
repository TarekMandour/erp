<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_number',
        'supplier_id',
        'warehouse_id',
        'date',
        'due_date',
        'payment_type',
        'status',
        'notes',
        'subtotal',
        'discount',
        'tax',
        'total',
        'paid',
    ];

    protected $casts = [
        'date'     => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax'      => 'decimal:2',
        'total'    => 'decimal:2',
        'paid'     => 'decimal:2',
    ];

    public static array $paymentTypeLabels = [
        'cash'         => 'نقدي',
        'credit'       => 'آجل',
        'installments' => 'أقساط',
    ];

    public static array $statusLabels = [
        'pending'            => 'معلق',
        'received'           => 'مستلم',
        'partially_received' => 'مستلم جزئياً',
        'cancelled'          => 'ملغي',
    ];

    public static array $statusBadges = [
        'pending'            => 'badge-light-warning',
        'received'           => 'badge-light-success',
        'partially_received' => 'badge-light-info',
        'cancelled'          => 'badge-light-danger',
    ];

    public function getStatusLabelAttribute(): string
    {
        return static::$statusLabels[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return static::$statusBadges[$this->status] ?? 'badge-light-secondary';
    }

    public function getRemainingAttribute(): string
    {
        return number_format((float)$this->total - (float)$this->paid, 2);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    // Auto-generate purchase number
    public static function generateNumber(): string
    {
        $last = static::orderByDesc('id')->value('purchase_number');
        if (!$last) return 'PO-0001';
        $num = (int) substr($last, 3);
        return 'PO-' . str_pad($num + 1, 4, '0', STR_PAD_LEFT);
    }
}
