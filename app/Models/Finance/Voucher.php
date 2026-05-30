<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use App\Models\Admin;

class Voucher extends Model
{
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
}
