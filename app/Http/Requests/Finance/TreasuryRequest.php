<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class TreasuryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->input('id');

        return [
            'name'     => 'required|string|max:255|unique:treasuries,name,' . ($id ?? 'NULL') . ',id',
            'account_tree_id'        => 'nullable|exists:account_trees,id',
            'currency' => 'required|string|max:10',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'     => 'اسم الخزنة',
            'account_tree_id' => 'شجرة الحساب',
            'currency' => 'العملة',
        ];
    }
}
