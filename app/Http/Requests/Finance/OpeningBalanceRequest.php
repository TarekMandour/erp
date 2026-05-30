<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class OpeningBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id'  => 'required|exists:account_trees,id',
            'debit'       => 'required|numeric|min:0',
            'credit'      => 'required|numeric|min:0',
            'date'        => 'required|date',
            'description' => 'nullable|string|max:500',
        ];
    }

    public function attributes(): array
    {
        return [
            'account_id'  => 'الحساب',
            'debit'       => 'مدين',
            'credit'      => 'دائن',
            'date'        => 'التاريخ',
            'description' => 'البيان',
        ];
    }
}
