<?php

namespace App\Models\Finance;

use App\Traits\Finance\HasJournalEntry;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin;

class Voucher extends Model
{
    use HasJournalEntry;

    protected $table = 'vouchers';

    protected $fillable = [
        'type',
        'payment_type',
        'party_type',
        'party_id',
        'party_name',
        'total_amount',
        'date',
        'description',
        'created_by',
        'financial_account_id',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'date'         => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function getTypeNameAttribute(): string
    {
        return match($this->type) {
            'payment' => 'سند صرف',
            'receipt' => 'سند قبض',
            default   => $this->type,
        };
    }

    public function getPaymentTypeNameAttribute(): string
    {
        return match($this->payment_type) {
            'cash'         => 'نقدي',
            'credit'       => 'آجل',
            'installments' => 'أقساط',
            'wallet'       => 'محفظة الكترونيه',
            'bank_online'  => 'تحويل بنكي اونلاين',
            'bank_direct'  => 'تحويل بنكي مباشر',
            'check'        => 'شيك',
            default        => $this->payment_type,
        };
    }

    public function getPartyTypeNameAttribute(): string
    {
        return match($this->party_type) {
            'customer' => 'عميل',
            'supplier' => 'مورد',
            'other'    => 'أخرى',
            default    => $this->party_type,
        };
    }

    // =========================================================================
    //  HasJournalEntry overrides
    // =========================================================================

    public function getScenarioCode(): string
    {
        return sprintf(
            'VOUCHER_%s_%s_%s',
            $this->type === 'receipt' ? 'RECEIPT' : 'PAYMENT',
            strtoupper($this->payment_type ?? 'CASH'),
            strtoupper($this->party_type  ?? 'OTHER')
        );
    }

    public function getFallbackScenarioCode(): string
    {
        return 'ADJUSTMENT';
    }

    public function getOperationType(): string
    {
        return 'voucher';
    }

    public function getEntryType(): string
    {
        return $this->type === 'receipt' ? 'customer_payment' : 'supplier_payment';
    }

    public function isReceiptType(): bool
    {
        return $this->type === 'receipt';
    }

    public function isPaymentType(): bool
    {
        return $this->type === 'payment';
    }

    public function getPaymentTypeLabel(): string
    {
        return match ($this->payment_type) {
            'cash'         => 'نقدي',
            'credit'       => 'آجل',
            'installments' => 'أقساط',
            'wallet'       => 'محفظة الكترونيه',
            'bank_online'  => 'تحويل بنكي اونلاين',
            'bank_direct'  => 'تحويل بنكي مباشر',
            'check'        => 'شيك',
            default        => $this->payment_type ?? '',
        };
    }

    public function getPostableDescription(): string
    {
        $typeText    = $this->type === 'receipt' ? 'سند قبض' : 'سند صرف';
        $paymentText = $this->getPaymentTypeLabel();

        $partyText = match (true) {
            $this->party_type === 'customer' && ! empty($this->party_id)
                => " للعميل رقم {$this->party_id}",
            $this->party_type === 'supplier' && ! empty($this->party_id)
                => " للمورد رقم {$this->party_id}",
            ! empty($this->party_name)
                => " لـ {$this->party_name}",
            default => '',
        };

        return "{$typeText} {$paymentText}{$partyText} - مبلغ: {$this->total_amount} - {$this->description}";
    }
}
