<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class UnitConversionRequest extends FormRequest
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
            'product_id' => 'nullable|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'base_unit' => 'required|exists:units,id',
            'target_unit' => 'required|different:base_unit|exists:units,id',
            'conversion_rate'   => 'required|numeric|gt:0',
            'is_default' => 'boolean',
            'allow_fractions' => 'boolean',
            'decimal_places'    => 'integer|min:0|max:6',
        ];
    }

    public function messages(): array
    {
        return [
            'conversion_rate.gt'     => 'معامل التحويل يجب أن يكون أكبر من صفر',
            'target_unit.different'  => 'الوحدة الهدف يجب أن تختلف عن الوحدة الأساسية',
        ];
    }
}
