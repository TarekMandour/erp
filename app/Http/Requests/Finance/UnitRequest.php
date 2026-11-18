<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
            'code' => ['nullable', 'string', 'max:100', Rule::unique('units', 'code')->ignore($this->input('id'))],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'كود الوحدة مستخدم من قبل (قد يكون لعنصر محذوف)، اختر كوداً آخر',
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
