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

    public function getEntryTypeLabelAttribute(): string
    {
        return match($this->entry_type) {
            'sales'      => 'مبيعات',
            'purchase'   => 'مشتريات',
            'receipt'    => 'قبض',
            'payment'    => 'صرف',
            'expense'    => 'مصروف',
            'transfer'   => 'تحويل',
            'opening'    => 'رصيد افتتاحي',
            'adjustment' => 'تسوية',
            default      => $this->entry_type,
        };
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
