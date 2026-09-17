<?php

namespace App\Models\Finance;

use App\Traits\Finance\HasJournalEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory, HasJournalEntry;

    protected $fillable = [
        'order_number',
        'customer_id',
        'warehouse_id',
        'date',
        'delivery_date',
        'payment_type',
        'status',
        'notes',
        'shipping_address',
        'subtotal',
        'discount',
        'tax',
        'shipping_cost',
        'coupon_id',
        'coupon_discount',
        'offer_id',
        'offer_discount',
        'total',
        'paid',
    ];

    protected $casts = [
        'date'          => 'date',
        'delivery_date' => 'date',
        'subtotal'      => 'decimal:2',
        'discount'      => 'decimal:2',
        'tax'           => 'decimal:2',
        'shipping_cost'  => 'decimal:2',
        'coupon_discount' => 'decimal:2',
        'offer_discount'  => 'decimal:2',
        'total'           => 'decimal:2',
        'paid'            => 'decimal:2',
    ];

    public static array $paymentTypeLabels = [
        'cash'         => 'نقدي',
        'credit'       => 'آجل',
        'installments' => 'أقساط',
        'wallet'       => 'محفظة الكترونيه',
        'bank_online'  => 'تحويل بنكي اونلاين',
        'bank_direct'  => 'تحويل بنكي مباشر',
        'check'        => 'شيك',
    ];

    public static array $statusLabels = [
        'draft'      => 'مسودة',
        'confirmed'  => 'مؤكد',
        'processing' => 'قيد التجهيز',
        'shipped'    => 'تم الشحن',
        'delivered'  => 'تم التسليم',
        'cancelled'  => 'ملغي',
    ];

    public static array $statusBadges = [
        'draft'      => 'badge-light-secondary',
        'confirmed'  => 'badge-light-primary',
        'processing' => 'badge-light-warning',
        'shipped'    => 'badge-light-info',
        'delivered'  => 'badge-light-success',
        'cancelled'  => 'badge-light-danger',
    ];

    // Statuses that cannot be edited / are "locked"
    public static array $lockedStatuses = ['shipped', 'delivered', 'cancelled'];

    public function getStatusLabelAttribute(): string
    {
        return static::$statusLabels[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return static::$statusBadges[$this->status] ?? 'badge-light-secondary';
    }

    public function getRemainingAttribute(): float
    {
        return round((float)$this->total - (float)$this->paid, 2);
    }

    public function getIsLockedAttribute(): bool
    {
        return in_array($this->status, static::$lockedStatuses);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public static function generateNumber(): string
    {
        $last = static::orderByDesc('id')->value('order_number');
        if (!$last) return 'SO-0001';
        $num = (int) substr($last, 3);
        return 'SO-' . str_pad($num + 1, 4, '0', STR_PAD_LEFT);
    }

    // =========================================================================
    //  HasJournalEntry overrides
    // =========================================================================

    public function getScenarioCode(): string
    {
        return 'ORDER_' . strtoupper($this->payment_type ?? 'CASH');
    }

    public function getFallbackScenarioCode(): string
    {
        return 'ORDER_CASH';
    }

    public function getOperationType(): string
    {
        return 'order';
    }

    public function getEntryType(): string
    {
        return 'sales';
    }

    public function isReceiptType(): bool
    {
        return true;
    }

    public function getTransactionAmount(): float
    {
        return (float) ($this->total ?? 0);
    }

    public function getPaymentTypeLabel(): string
    {
        return static::$paymentTypeLabels[$this->payment_type] ?? ($this->payment_type ?? '');
    }

    public function getPostableDescription(): string
    {
        return "فاتورة مبيعات رقم {$this->order_number}";
    }
}
