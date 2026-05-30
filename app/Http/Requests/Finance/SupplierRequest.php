<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->id;

        return [
            'company_name'       => 'required|string|max:255|unique:suppliers,company_name,' . $id,
            'name'               => 'required|string|max:255',
            'phone'              => 'nullable|string|max:20',
            'address'            => 'nullable|string',
            'tax_number'         => 'nullable|string|max:100',
            'commercial_number'  => 'nullable|string|max:100',
            'bank'               => 'nullable|string|max:255',
            'bank_account'       => 'nullable|string|max:100',
            'account_status'     => 'required|in:active,inactive,blocked',
        ];
    }
}
