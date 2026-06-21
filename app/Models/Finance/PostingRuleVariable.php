<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class PostingRuleVariable extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'rule_id',
        'variable_name',
        'source_type',
        'source_value',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function rule()
    {
        return $this->belongsTo(PostingRule::class, 'rule_id');
    }

    public function getSourceTypeLabelAttribute(): string
    {
        return match($this->source_type) {
            'field'    => 'حقل',
            'function' => 'دالة',
            'subquery' => 'استعلام فرعي',
            default    => $this->source_type,
        };
    }
}
