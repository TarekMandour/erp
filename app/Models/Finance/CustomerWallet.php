<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'voucher_id',
        'date',
        'description',
        'debit',
        'credit',
        'balance',
    ];

    protected $casts = [
        'date'    => 'date',
        'debit'   => 'decimal:2',
        'credit'  => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
