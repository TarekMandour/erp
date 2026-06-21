<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class BankAccountRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->id;
        return [
            'bank_id'        => 'required|exists:banks,id',
            'account_tree_id'        => 'nullable|exists:account_trees,id',
            'account_number' => 'required|string|max:100|unique:bank_accounts,account_number,' . $id,
            'currency'       => 'required|string|max:10',
        ];
    }
}
