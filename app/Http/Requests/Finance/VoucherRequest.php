<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class VoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $partyType = $this->input('party_type');

        return [
            'type'         => 'required|in:payment,receipt',
            'payment_type' => 'required|in:cash,credit,installments',
            'party_type'   => 'required|in:customer,supplier,other',
            'party_id'     => $partyType !== 'other' ? 'required|integer|min:1' : 'nullable|integer',
            'party_name'   => $partyType === 'other' ? 'required|string|max:200' : 'nullable|string|max:200',
            'total_amount' => 'required|numeric|min:0.01',
            'date'         => 'required|date',
            'description'          => 'nullable|string|max:1000',
            'financial_account_id' => 'nullable|integer|exists:account_trees,id',
        ];
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
        ];
    }
}
