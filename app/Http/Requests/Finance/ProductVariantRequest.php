<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class ProductVariantRequest extends FormRequest
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
            'sku' => 'required|unique:product_variants,sku,'.$this->id,
            'barcode' => 'nullable|unique:product_variants,barcode,'.$this->id,
            'attributes' => 'nullable|array',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'average_cost' => 'nullable|numeric|min:0',
            'alert_quantity' => 'nullable|integer|min:0',
            'sort_order' => 'nullable',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'weight' => 'nullable',
            'width' => 'nullable',
            'height' => 'nullable',
            'length' => 'nullable',
        ];
    }
}
