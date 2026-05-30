<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class TransAccountTreeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id'     => 'required|exists:account_trees,id',
            'debit'          => 'nullable|numeric|min:0',
            'credit'         => 'nullable|numeric|min:0',
            'reference_type' => 'nullable|string|max:255',
            'reference_id'   => 'nullable|integer|min:1',
            'description'    => 'nullable|string',
        ];
    }
}
