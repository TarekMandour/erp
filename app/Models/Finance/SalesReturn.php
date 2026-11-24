<?php

namespace App\Models\Finance;

use App\Traits\Finance\HasJournalEntry;
use Illuminate\Database\Eloquent\Model;

/**
 * مرتجع مبيعات: مستند مستقل يُنشأ من طلب بيع أصلي ولا يعدّله.
 * يُرحَّل بسيناريوهات ORDER_RETURN_* (نوع القيد sales_return).
 */
class SalesReturn extends Model
{
    use HasJournalEntry;

    protected $fillable = [
        'return_number',
        'order_id',
        'customer_id',
        'warehouse_id',
        'date',
        'payment_type',
        'bank_account_id',
        'status',
        'is_full',
        'include_shipping',
        'notes',
        'subtotal',
        'discount',
        'tax',
        'shipping_cost',
        'coupon_discount',
        'offer_discount',
        'total',
        'paid',
        'created_by',
    ];

    protected $casts = [
        'date'             => 'date',
        'is_full'          => 'boolean',
        'include_shipping' => 'boolean',
        'subtotal'         => 'decimal:2',
        'discount'         => 'decimal:2',
        'tax'              => 'decimal:2',
        'shipping_cost'    => 'decimal:2',
        'coupon_discount'  => 'decimal:2',
        'offer_discount'   => 'decimal:2',
        'total'            => 'decimal:2',
        'paid'             => 'decimal:2',
    ];

    public static array $statusLabels = [
        'confirmed' => 'مؤكد',
        'cancelled' => 'ملغي',
    ];

    public static array $statusBadges = [
        'confirmed' => 'badge-light-success',
        'cancelled' => 'badge-light-danger',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function items()
    {
        return $this->hasMany(SalesReturnItem::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return static::$statusLabels[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return static::$statusBadges[$this->status] ?? 'badge-light-secondary';
    }

    /** ما يخفض ذمة العميل في المحفظة والقيد (الإجمالي − المردود) */
    public function getReceivableReductionAttribute(): float
    {
        return round((float) $this->total - (float) $this->paid, 2);
    }

    public static function generateNumber(): string
    {
        $last = static::orderByDesc('id')->value('return_number');
        if (! $last) {
            return 'SR-0001';
        }

        return 'SR-' . str_pad(((int) substr($last, 3)) + 1, 4, '0', STR_PAD_LEFT);
    }

    // =========================================================================
    //  HasJournalEntry overrides
    // =========================================================================

    public function getScenarioCode(): string
    {
        return 'ORDER_RETURN_' . strtoupper($this->payment_type ?? 'CASH');
    }

    public function getFallbackScenarioCode(): string
    {
        return 'ORDER_RETURN_CASH';
    }

    public function getOperationType(): string
    {
        return 'sales_return';
    }

    public function getEntryType(): string
    {
        return 'sales_return';
    }

    /** بادئة ترقيم القيد (يستخدمها JournalEntryBuilder إن وُجدت) */
    public function getEntryPrefix(): string
    {
        return 'SRT';
    }

    public function isPaymentType(): bool
    {
        return true;
    }

    public function getTransactionAmount(): float
    {
        return (float) ($this->total ?? 0);
    }

    public function getPaymentTypeLabel(): string
    {
        return Order::$paymentTypeLabels[$this->payment_type] ?? ($this->payment_type ?? '');
    }

    public function getPostableDescription(): string
    {
        return "مرتجع مبيعات رقم {$this->return_number} عن طلب {$this->order?->order_number}";
    }
}
