<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class SupplierWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => 'required|exists:suppliers,id',
            'date'        => 'required|date',
            'description' => 'nullable|string',
            'debit'       => 'required|numeric|min:0',
            'credit'      => 'required|numeric|min:0',
            'counter_account_id' => 'required|exists:account_trees,id',
        ];
    }

    public function attributes(): array
    {
        return ['debit' => 'مدين', 'credit' => 'دائن', 'date' => 'التاريخ', 'counter_account_id' => 'الحساب المقابل'];
    }

    /**
     * QA 4.3: مدين فقط أو دائن فقط، والحساب المقابل فرعي نشط وليس خزنة/بنكاً ولا حساب الطرف (2101)
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            if (((float) $this->input('debit') > 0) === ((float) $this->input('credit') > 0)) {
                $validator->errors()->add('debit', 'أدخل مبلغاً مديناً أو دائناً فقط (أحدهما أكبر من صفر والآخر صفر)');
            }
            $allowed = app(\App\Services\Finance\WalletJournalService::class)->counterAccounts('2101')->pluck('id')->all();
            if (! in_array((int) $this->input('counter_account_id'), $allowed, true)) {
                $validator->errors()->add('counter_account_id', 'الحساب المقابل يجب أن يكون حساباً فرعياً نشطاً، وليس خزنة أو بنكاً أو حساب الموردين نفسه');
            }
        });
    }
}
