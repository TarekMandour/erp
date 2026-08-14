<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class AccountTreeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->id;

        return [
            'name'      => 'required|string|max:255',
            'code'      => 'required|string|max:100|unique:account_trees,code,' . $id,
            'parent_id' => 'nullable|exists:account_trees,id',
            'account_type' => 'nullable|in:debit,credit',
            'type'      => 'required|in:asset,liability,equity,revenue,expense',
        ];
    }
}
