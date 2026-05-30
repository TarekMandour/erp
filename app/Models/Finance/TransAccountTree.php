<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransAccountTree extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'debit',
        'credit',
        'reference_type',
        'reference_id',
        'description',
    ];

    protected $casts = [
        'debit'  => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function account()
    {
        return $this->belongsTo(AccountTree::class, 'account_id');
    }
}
