<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class TreasuryTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'treasury_id'    => 'required|exists:treasuries,id',
            'type'           => 'required|in:deposit,withdraw',
            'amount'         => 'required|numeric|min:0.01',
            'counter_account_id' => 'required|exists:account_trees,id',
            'description'    => 'nullable|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'treasury_id' => 'الخزنة',
            'type'        => 'نوع المعاملة',
            'amount'      => 'المبلغ',
            'description' => 'البيان',
            'counter_account_id' => 'الحساب المقابل',
        ];
    }

    /**
     * الحساب المقابل: فرعي نشط، وليس خزنة أو حساباً بنكياً
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $id = $this->input('counter_account_id');
            if ($validator->errors()->has('counter_account_id') || ! $id) {
                return;
            }
            $allowed = app(\App\Services\Finance\ManualJournalService::class)->counterAccounts()->pluck('id')->all();
            if (! in_array((int) $id, $allowed, true)) {
                $validator->errors()->add('counter_account_id', 'الحساب المقابل يجب أن يكون حساباً فرعياً نشطاً وليس خزنة أو حساباً بنكياً');
            }
        });
    }
}
