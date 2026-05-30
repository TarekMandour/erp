<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'max_discount',
        'min_order_amount',
        'usage_limit',
        'usage_per_user',
        'used_count',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'start_date'       => 'date',
        'end_date'         => 'date',
        'value'            => 'decimal:2',
        'max_discount'     => 'decimal:2',
        'min_order_amount' => 'decimal:2',
    ];

    public function isExpired(): bool
    {
        return $this->end_date->isPast();
    }

    public function isUsageLimitReached(): bool
    {
        return $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
    }
}
