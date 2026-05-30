<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class OpeningBalance extends Model
{
    protected $table = 'opening_balances';

    protected $fillable = [
        'account_id',
        'debit',
        'credit',
        'date',
        'description',
    ];

    protected $casts = [
        'debit'  => 'decimal:2',
        'credit' => 'decimal:2',
        'date'   => 'date',
    ];

    public function account()
    {
        return $this->belongsTo(AccountTree::class, 'account_id');
    }
}
