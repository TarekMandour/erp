<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'voucher_id',
        'date',
        'description',
        'debit',
        'credit',
        'previous_balance',
        'balance',
    ];

    protected $casts = [
        'date'             => 'date',
        'debit'            => 'decimal:2',
        'credit'           => 'decimal:2',
        'previous_balance' => 'decimal:2',
        'balance'          => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
