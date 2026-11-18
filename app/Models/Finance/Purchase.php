<?php

namespace App\Models\Finance;

use App\Traits\Finance\HasJournalEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Model
{
    use HasFactory, HasJournalEntry;

    protected $fillable = [
        'purchase_number',
        'supplier_id',
        'warehouse_id',
        'date',
        'due_date',
        'payment_type',
        'bank_account_id',
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
        'wallet'       => 'محفظة الكترونيه',
        'bank_online'  => 'تحويل بنكي اونلاين',
        'bank_direct'  => 'تحويل بنكي مباشر',
        'check'        => 'شيك',
    ];

    public static array $statusLabels = [
        // 'pending'            => 'معلق',
        'received'           => 'مكتمل',
        'cancelled'          => 'ملغي',
    ];

    public static array $statusBadges = [
        // 'pending'            => 'badge-light-warning',
        'received'           => 'badge-light-success',
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
        return (float)$this->total - (float)$this->paid;
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
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
        return $this->hasMany(PurchaseItem::class);
    }

    // Auto-generate purchase number
    public static function generateNumber(): string
    {
        // أعلى رقم مستخدم (وليس رقم آخر صف) ثم التقدّم حتى رقم غير مستخدم
        // (الحلقة القديمة كانت تعيد نفس المرشّح فتتعلق إذا كان رقم آخر صف + 1 محجوزاً)
        $num = (int) static::where('purchase_number', 'like', 'PO-%')
            ->pluck('purchase_number')
            ->map(fn ($n) => (int) substr($n, 3))
            ->max() + 1;

        do {
            $candidate = 'PO-' . str_pad($num++, 4, '0', STR_PAD_LEFT);
        } while (static::where('purchase_number', $candidate)->exists());

        return $candidate;
    }

    // =========================================================================
    //  HasJournalEntry overrides
    // =========================================================================

    public function getScenarioCode(): string
    {
        return 'PURCHASE_' . strtoupper($this->payment_type ?? 'CREDIT');
    }

    public function getFallbackScenarioCode(): string
    {
        return 'PURCHASE_CASH';
    }

    public function getOperationType(): string
    {
        return 'purchase';
    }

    public function getEntryType(): string
    {
        return 'purchase';
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
        return static::$paymentTypeLabels[$this->payment_type] ?? ($this->payment_type ?? '');
    }

    public function getPostableDescription(): string
    {
        return "فاتورة مشتريات رقم {$this->purchase_number}";
    }
}
