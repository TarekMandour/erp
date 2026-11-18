<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrandRequest extends FormRequest
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
            // QA 2.1/2.2/2.5/2.6: الكود فريد (يشمل العناصر المحذوفة مؤقتاً لأن فهرس القاعدة يشملها)
            'code' => ['nullable', 'string', 'max:100', Rule::unique('brands', 'code')->ignore($this->input('id'))],
            'description' => 'nullable',
            'sort' => 'required|min:0',
            'is_active' => 'boolean',
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp',Rule::requiredIf($this->routeIs('finance.category.store'))],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'كود الماركة مستخدم من قبل (قد يكون لعنصر محذوف)، اختر كوداً آخر',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'الاسم', 'code' => 'الكود', 'description' => 'الوصف', 'sort' => 'الترتيب',
            'is_active' => 'الحالة', 'image' => 'الصورة', 'parent_id' => 'الفئة الأب',
        ];
    }
}
