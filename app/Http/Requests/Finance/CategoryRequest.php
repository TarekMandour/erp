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
            'parent_id' => ['nullable', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            // QA 2.1/2.2/2.5/2.6: الكود فريد (يشمل العناصر المحذوفة مؤقتاً لأن فهرس القاعدة يشملها)
            'code' => ['nullable', 'string', 'max:100', Rule::unique('categories', 'code')->ignore($this->input('id'))],
            'description' => 'nullable',
            'sort' => 'required|min:0',
            'is_active' => 'boolean',
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'كود الفئة مستخدم من قبل (قد يكون لعنصر محذوف)، اختر كوداً آخر',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'الاسم', 'code' => 'الكود', 'description' => 'الوصف', 'sort' => 'الترتيب',
            'is_active' => 'الحالة', 'image' => 'الصورة', 'parent_id' => 'الفئة الأب',
        ];
    }

    /**
     * QA 2.3: لا فئة أب لنفسها ولا حلقات
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $id       = (int) $this->input('id');
            $parentId = (int) $this->input('parent_id');
            if (! $id || ! $parentId) {
                return;
            }
            if ($id === $parentId) {
                $validator->errors()->add('parent_id', 'لا يمكن أن تكون الفئة أباً لنفسها');
                return;
            }
            $cursor  = \App\Models\Finance\Category::withTrashed()->find($parentId);
            $visited = [];
            while ($cursor && ! isset($visited[$cursor->id])) {
                if ((int) $cursor->id === $id) {
                    $validator->errors()->add('parent_id', 'لا يمكن نقل الفئة تحت إحدى فئاتها الفرعية (حلقة)');
                    return;
                }
                $visited[$cursor->id] = true;
                $cursor = $cursor->parent_id ? \App\Models\Finance\Category::withTrashed()->find($cursor->parent_id) : null;
            }
        });
    }
}
