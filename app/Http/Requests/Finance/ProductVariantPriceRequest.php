<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class ProductVariantPriceRequest extends FormRequest
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
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'sale_start' => 'nullable',
            'sale_end' => 'nullable',
            'quantity_prices' => 'nullable|array',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_group_id' => 'nullable',
            'valid_from' => 'nullable',
            'valid_to' => 'nullable',
            'is_active' => 'boolean',
            'priority' => 'nullable',
        ];
    }
}
