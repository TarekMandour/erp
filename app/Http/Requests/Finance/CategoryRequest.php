<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
{
    $this->merge([
        'is_active' => $this->has('is_active'),
    ]);
}

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable',
            'code' => 'nullable',
            'description' => 'nullable',
            'sort' => 'required|min:0',
            'is_active' => 'boolean',
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp'],
        ];
    }
}
