<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class BankRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->id;
        return [
            'name' => 'required|string|max:255|unique:banks,name,' . $id,
        ];
    }
}
