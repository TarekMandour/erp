<?php

namespace App\Http\Requests\Finance;

use App\Models\Finance\AccountTree;
use App\Models\Finance\BankAccount;
use App\Models\Finance\Treasury;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class VoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // الحساب المقابل خاص بالطرف «أخرى» فقط
        if ($this->input('party_type') !== 'other') {
            $this->merge(['counter_account_id' => null]);
        }
    }

    public function rules(): array
    {
        $partyType = $this->input('party_type');

        return [
            'type'         => 'required|in:payment,receipt',
            'payment_type' => 'required|in:cash,credit,installments,wallet,bank_online,bank_direct,check',
            'party_type'   => 'required|in:customer,supplier,other',
            'party_id'     => $partyType !== 'other' ? 'required|integer|min:1' : 'nullable|integer',
            'party_name'   => $partyType === 'other' ? 'required|string|max:200' : 'nullable|string|max:200',
            'total_amount' => 'required|numeric|min:0.01',
            'date'         => 'required|date',
            'description'          => 'nullable|string|max:1000',
            'financial_account_id' => 'nullable|integer|exists:account_trees,id',
            'counter_account_id'   => 'nullable|integer|exists:account_trees,id',
        ];
    }

    /**
     * الحساب المقابل للطرف «أخرى» (QA 2.5): حساب فرعي مفعّل وليس خزنة أو بنكاً
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $id = $this->input('counter_account_id');
            if (! $id || $v->errors()->has('counter_account_id')) {
                return;
            }

            $account = AccountTree::find($id);
            if (! $account || ! $account->is_active) {
                $v->errors()->add('counter_account_id', 'الحساب المقابل غير مفعّل.');
            } elseif (AccountTree::where('parent_id', $id)->exists()) {
                $v->errors()->add('counter_account_id', 'لا يمكن الترحيل على حساب رئيسي؛ اختر حساباً فرعياً.');
            } elseif (Treasury::where('account_tree_id', $id)->exists() || BankAccount::where('account_tree_id', $id)->exists()) {
                $v->errors()->add('counter_account_id', 'الحساب المقابل لا يكون خزنة أو بنكاً؛ استخدم «البند المالي» لذلك.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'type'         => 'نوع السند',
            'payment_type' => 'نوع الدفع',
            'party_type'   => 'نوع الطرف',
            'party_id'     => 'الطرف',
            'party_name'   => 'اسم الطرف',
            'total_amount' => 'المبلغ الإجمالي',
            'date'         => 'التاريخ',
            'description'          => 'البيان',
            'financial_account_id' => 'البند المالي',
            'counter_account_id'   => 'الحساب المقابل',
        ];
    }
}
