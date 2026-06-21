<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use App\Models\Admin;

class PostingRule extends Model
{
    protected $fillable = [
        'scenario_id',
        'rule_group',
        'debit_account_id',
        'credit_account_id',
        'amount_type',
        'amount_value',
        'amount_field',
        'formula',
        'conditions',
        'cost_center_source',
        'fixed_cost_center_id',
        'is_required',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'conditions'  => 'array',
        'is_required' => 'boolean',
        'amount_value' => 'decimal:2',
    ];

    public function scenario()
    {
        return $this->belongsTo(PostingScenario::class, 'scenario_id');
    }

    public function debitAccount()
    {
        return $this->belongsTo(AccountTree::class, 'debit_account_id');
    }

    public function creditAccount()
    {
        return $this->belongsTo(AccountTree::class, 'credit_account_id');
    }

    public function fixedCostCenter()
    {
        return $this->belongsTo(CostCenter::class, 'fixed_cost_center_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function variables()
    {
        return $this->hasMany(PostingRuleVariable::class, 'rule_id');
    }

    public function getAmountTypeLabelAttribute(): string
    {
        return match($this->amount_type) {
            'fixed'         => 'قيمة ثابتة',
            'subtotal'      => 'المجموع الفرعي',
            'tax'           => 'الضريبة',
            'total'         => 'الإجمالي',
            'quantity_cost' => 'الكمية × التكلفة',
            'percentage'    => 'نسبة مئوية',
            'formula'       => 'معادلة',
            default         => $this->amount_type,
        };
    }
}
