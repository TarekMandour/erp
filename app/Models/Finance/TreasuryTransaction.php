<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class TreasuryTransaction extends Model
{
    protected $table = 'treasury_transactions';

    protected $fillable = [
        'treasury_id',
        'type',
        'amount',
        'description',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function treasury()
    {
        return $this->belongsTo(Treasury::class);
    }

    public function getTypeNameAttribute(): string
    {
        return match ($this->type) {
            'deposit'  => 'إيداع',
            'withdraw' => 'سحب',
            default    => $this->type,
        };
    }
}
