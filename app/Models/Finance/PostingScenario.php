<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Admin;
use App\Models\Finance\JournalEntry;

class PostingScenario extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'operation_type',
        'is_active',
        'priority',
        'description',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority'  => 'integer',
    ];

    public function rules()
    {
        return $this->hasMany(PostingRule::class, 'scenario_id')->orderBy('sort_order');
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function getOperationTypeLabelAttribute(): string
    {
        return JournalEntry::entryTypeLabels()[$this->operation_type] ?? $this->operation_type;
    }
}
