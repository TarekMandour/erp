<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Admin;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_number',
        'entry_type',
        'date',
        'description',
        'reference_type',
        'reference_id',
        'is_reversed',
        'reversed_from',
        'created_by',
        'approved_by',
        'approved_at',
        'status',
    ];

    protected $casts = [
        'date'        => 'date',
        'approved_at' => 'datetime',
        'is_reversed' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(JournalEntryItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function reversedFromEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'reversed_from');
    }

    public static function entryTypeLabels(): array
    {
        return [
            'sales'               => 'مبيعات',
            'sales_return'        => 'مرتجع مبيعات',
            'sales_discount'      => 'خصم مبيعات',
            'sales_installment'   => 'مبيعات بالتقسيط',
            'purchase'            => 'مشتريات',
            'purchase_return'     => 'مرتجع مشتريات',
            'purchase_discount'   => 'خصم مشتريات',
            'inventory_in'          => 'وارد مخزون',
            'inventory_out'         => 'صادر مخزون',
            'inventory_transfer'    => 'تحويل مخزون',
            'inventory_adjustment'  => 'تسوية مخزون',
            'inventory_write_off'   => 'إتلاف مخزون',
            'inventory_revaluation' => 'إعادة تقييم مخزون',
            'cash_deposit'    => 'إيداع صندوق',
            'cash_withdraw'   => 'سحب صندوق',
            'cash_transfer'   => 'تحويل صندوق',
            'bank_deposit'    => 'إيداع بنك',
            'bank_withdraw'   => 'سحب بنك',
            'bank_transfer'   => 'تحويل بنك',
            'cheque_received' => 'شيك مستلم',
            'cheque_issued'   => 'شيك صادر',
            'cheque_cashed'   => 'صرف شيك',
            'wallet_deposit'  => 'إيداع محفظة',
            'wallet_withdraw' => 'سحب محفظة',
            'wallet_payment'  => 'دفع محفظة',
            'wallet_refund'   => 'استرداد محفظة',
            'customer_payment'     => 'تحصيل عميل',
            'customer_credit_note' => 'إشعار دائن عميل',
            'customer_debit_note'  => 'إشعار مدين عميل',
            'supplier_payment'     => 'دفع مورد',
            'supplier_credit_note' => 'إشعار دائن مورد',
            'supplier_debit_note'  => 'إشعار مدين مورد',
            'expense'          => 'مصروف',
            'revenue'          => 'إيراد',
            'accrued_expense'  => 'مصروف مستحق',
            'prepaid_expense'  => 'مصروف مدفوع مقدماً',
            'depreciation'     => 'إهلاك',
            'amortization'     => 'استهلاك',
            'salary'          => 'راتب',
            'salary_advance'  => 'سلفة راتب',
            'salary_loan'     => 'قرض موظف',
            'overtime'        => 'عمل إضافي',
            'bonus'           => 'مكافأة',
            'commission'      => 'عمولة',
            'asset_purchase'     => 'شراء أصل',
            'asset_sale'         => 'بيع أصل',
            'asset_disposal'     => 'استبعاد أصل',
            'asset_depreciation' => 'إهلاك أصل',
            'vat_input'       => 'ضريبة مدخلات',
            'vat_output'      => 'ضريبة مخرجات',
            'vat_payment'     => 'دفع ضريبة',
            'vat_refund'      => 'استرداد ضريبة',
            'income_tax'      => 'ضريبة دخل',
            'withholding_tax' => 'خصم المصدر',
            'loan_received' => 'استلام قرض',
            'loan_payment'  => 'سداد قرض',
            'loan_interest' => 'فوائد قرض',
            'capital_increase' => 'زيادة رأس المال',
            'capital_decrease' => 'تخفيض رأس المال',
            'dividend_paid'    => 'توزيعات أرباح',
            'internal_transfer' => 'تحويل داخلي',
            'cost_allocation'   => 'تخصيص تكلفة',
            'profit_transfer'   => 'ترحيل أرباح',
            'opening_balance' => 'رصيد افتتاحي',
            'adjustment'      => 'تسوية',
            'revaluation'     => 'إعادة تقييم',
            'closing_entry'   => 'قيد إقفال',
            'refund'      => 'استرداد',
            'write_off'   => 'شطب',
            'reversal'    => 'عكس قيد',
            'correction'  => 'تصحيح',
        ];
    }

    public function getEntryTypeLabelAttribute(): string
    {
        return self::entryTypeLabels()[$this->entry_type] ?? $this->entry_type;
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft'    => 'مسودة',
            'posted'   => 'مرحّل',
            'canceled' => 'ملغي',
            default    => $this->status,
        };
    }
}
