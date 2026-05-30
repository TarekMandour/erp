<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class BankTransactionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'type'            => 'required|in:deposit,withdraw',
            'amount'          => 'required|numeric|min:0.01',
            'reference_type'  => 'nullable|string|max:255',
            'reference_id'    => 'nullable|integer|min:1',
        ];
    }
}
