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
            'description'    => 'nullable|string|max:500',
            'reference_type' => 'nullable|string|max:100',
            'reference_id'   => 'nullable|integer',
        ];
    }

    public function attributes(): array
    {
        return [
            'treasury_id' => 'الخزنة',
            'type'        => 'نوع المعاملة',
            'amount'      => 'المبلغ',
            'description' => 'البيان',
        ];
    }
}
