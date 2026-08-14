<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountTree extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'parent_id',
        'type',
        'account_type',
        'level',
        'total_debit',
        'total_credit',
        'balance',
        'is_active',
    ];

    protected $casts = [
        'total_debit'  => 'decimal:2',
        'total_credit' => 'decimal:2',
        'balance'      => 'decimal:2',
        'is_active'    => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(AccountTree::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(AccountTree::class, 'parent_id');
    }

    public function transactions()
    {
        return $this->hasMany(JournalEntryItem::class, 'account_tree_id');
    }

    public function getTypeNameAttribute(): string
    {
        return match($this->type) {
            'asset'     => 'أصول',
            'liability' => 'خصوم',
            'equity'    => 'حقوق ملكية',
            'revenue'   => 'إيرادات',
            'expense'   => 'مصروفات',
            default     => $this->type,
        };
    }
}
