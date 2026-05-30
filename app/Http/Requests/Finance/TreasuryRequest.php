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
            'currency' => 'required|string|max:10',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'     => 'اسم الخزنة',
            'currency' => 'العملة',
        ];
    }
}
