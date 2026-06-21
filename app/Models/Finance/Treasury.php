<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class Treasury extends Model
{
    protected $table = 'treasuries';

    protected $fillable = [
        'name',
        'account_tree_id',
        'currency',
        'balance',
        'is_active',
    ];

    protected $casts = [
        'balance'   => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function transactions()
    {
        return $this->hasMany(TreasuryTransaction::class);
    }
}
