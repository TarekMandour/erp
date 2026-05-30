<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class CustomerWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'date'        => 'required|date',
            'description' => 'nullable|string',
            'debit'       => 'required|numeric|min:0',
            'credit'      => 'required|numeric|min:0',
            'balance'     => 'required|numeric',
        ];
    }
}
