<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostingRuleVariableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rule_id'       => ['required', 'exists:posting_rules,id'],
            'variable_name' => ['required', 'string', 'max:100'],
            'source_type'   => ['required', Rule::in(['field', 'function', 'subquery'])],
            'source_value'  => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'rule_id'       => 'القاعدة',
            'variable_name' => 'اسم المتغير',
            'source_type'   => 'نوع المصدر',
            'source_value'  => 'قيمة المصدر',
        ];
    }
}
