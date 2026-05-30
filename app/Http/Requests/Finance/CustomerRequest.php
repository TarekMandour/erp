<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->id;

        return [
            'customer_code'  => 'required|string|max:100|unique:customers,customer_code,' . $id,
            'name'           => 'required|string|max:255',
            'phone'          => 'required|string|max:20|unique:customers,phone,' . $id,
            'email'          => 'nullable|email|max:255',
            'address'        => 'nullable|string',
            'account_status' => 'required|in:active,inactive,blocked',
            'join_date'      => 'required|date',
            'notes'          => 'nullable|string',
            'admin_id'       => 'nullable|exists:admins,id',
            'tax_number'     => 'nullable|string|max:100',
        ];
    }
}

