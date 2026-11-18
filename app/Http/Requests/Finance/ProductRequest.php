<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
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
            'sku' => 'required|unique:products,sku,'.$this->id,
            'barcode' => 'nullable|unique:products,sku,'.$this->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_id' => 'nullable|exists:units,id',
            'sort' => 'nullable',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'average_cost' => 'nullable|numeric|min:0',
            'calories' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'alert_quantity' => 'nullable|min:0',
            'is_active' => 'boolean',
            'has_expiry' => 'boolean',
            'has_variants' => 'boolean',
            'thumbnail' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg'],
            'gallery' => 'nullable|array',
            'gallery.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    public function attributes(): array
    {
        return [
            'sku' => 'SKU', 'barcode' => 'الباركود', 'name' => 'اسم المنتج', 'category_id' => 'الفئة', 'brand_id' => 'الماركة',
            'unit_id' => 'الوحدة', 'purchase_price' => 'سعر الشراء', 'selling_price' => 'سعر البيع', 'cost_price' => 'سعر التكلفة',
            'average_cost' => 'متوسط التكلفة', 'calories' => 'السعرات الحرارية', 'tax_rate' => 'نسبة الضريبة',
            'alert_quantity' => 'حد التنبيه', 'thumbnail' => 'الصورة', 'gallery' => 'معرض الصور',
        ];
    }
}
