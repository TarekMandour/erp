<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'name',
        'phone',
        'address',
        'tax_number',
        'commercial_number',
        'bank',
        'bank_account',
        'wallet_balance',
        'total_orders',
        'account_status',
    ];

    protected $casts = [
        'wallet_balance' => 'decimal:2',
    ];

    public function wallets()
    {
        return $this->hasMany(SupplierWallet::class);
    }
}
