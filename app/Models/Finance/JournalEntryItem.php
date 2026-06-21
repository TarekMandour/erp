<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class JournalEntryItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'journal_entry_id',
        'account_tree_id',
        'debit',
        'credit',
        'cost_center_id',
        'description',
    ];

    protected $casts = [
        'debit'      => 'decimal:2',
        'credit'     => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function entry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account()
    {
        return $this->belongsTo(AccountTree::class, 'account_tree_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }
}
