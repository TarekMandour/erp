<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class OpeningBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id'  => ['required', 'exists:account_trees,id', \Illuminate\Validation\Rule::unique('opening_balances', 'account_id')->ignore($this->input('id'))],
            'debit'       => 'required|numeric|min:0',
            'credit'      => 'required|numeric|min:0',
            'date'        => 'required|date',
            'description' => 'nullable|string|max:500',
        ];
    }

    public function attributes(): array
    {
        return [
            'account_id'  => 'الحساب',
            'debit'       => 'مدين',
            'credit'      => 'دائن',
            'date'        => 'التاريخ',
            'description' => 'البيان',
        ];
    }

    public function messages(): array
    {
        return [
            'account_id.unique' => 'يوجد رصيد افتتاحي لهذا الحساب بالفعل، عدّله بدلاً من إضافة سطر جديد',
        ];
    }

    /**
     * مدين فقط أو دائن فقط (> 0)، وعلى حساب فرعي نشط
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $debit  = (float) $this->input('debit');
            $credit = (float) $this->input('credit');
            if (($debit > 0) === ($credit > 0)) {
                $validator->errors()->add('debit', 'أدخل مبلغاً مديناً أو دائناً فقط (أحدهما أكبر من صفر والآخر صفر)');
            }

            $account = \App\Models\Finance\AccountTree::withCount('children')->find($this->input('account_id'));
            if ($account && ($account->children_count > 0 || ! $account->is_active)) {
                $validator->errors()->add('account_id', 'اختر حساباً فرعياً نشطاً، لا يمكن وضع رصيد على حساب رئيسي');
            }
        });
    }
}
