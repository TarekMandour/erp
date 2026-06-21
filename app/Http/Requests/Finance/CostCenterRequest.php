<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CostCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->input('id');

        return [
            'code'      => ['required', 'string', 'max:50', Rule::unique('cost_centers', 'code')->ignore($id)],
            'name'      => 'required|string|max:255',
            'parent_id' => 'nullable|exists:cost_centers,id',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'  => 'كود مركز التكلفة مطلوب',
            'code.unique'    => 'كود مركز التكلفة موجود مسبقاً',
            'name.required'  => 'اسم مركز التكلفة مطلوب',
        ];
    }
}
