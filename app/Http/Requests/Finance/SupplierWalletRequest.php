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
        ];
    }
}
