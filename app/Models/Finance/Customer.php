<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_code',
        'name',
        'phone',
        'email',
        'address',
        'account_status',
        'wallet_balance',
        'total_orders',
        'total_spent',
        'join_date',
        'notes',
        'admin_id',
        'tax_number',
    ];

    protected $casts = [
        'wallet_balance' => 'decimal:2',
        'total_spent'    => 'decimal:2',
        'join_date'      => 'date',
    ];

    public function wallets()
    {
        return $this->hasMany(CustomerWallet::class);
    }
}
