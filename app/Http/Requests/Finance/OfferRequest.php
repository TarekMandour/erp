<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Finance\Offer;

class OfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active'    => $this->has('is_active'),
            'is_stackable' => $this->has('is_stackable'),
        ]);
    }

    public function rules(): array
    {
        $id   = $this->input('id');
        $type = $this->input('type', '');

        return [
            'name'                => ['required', 'string', 'max:255', Rule::unique('offers', 'name')->ignore($id)],
            'description'         => 'nullable|string|max:1000',
            'type'                => ['required', Rule::in(array_keys(Offer::$typeLabels))],
            'applies_to'          => ['required', Rule::in(array_keys(Offer::$appliesToLabels))],
            'start_date'          => 'required|date',
            'end_date'            => 'required|date|after_or_equal:start_date',
            'is_active'           => 'boolean',
            'is_stackable'        => 'boolean',
            'priority'            => 'nullable|integer|min:0|max:999',
            'max_uses'            => 'nullable|integer|min:1',

            // value required for fixed-amount / percentage types
            'value' => [
                Rule::requiredIf(in_array($type, ['percentage', 'fixed', 'product_price_discount', 'bundle', 'first_order'])),
                'nullable', 'numeric', 'min:0',
            ],

            // buy/get quantities
            'buy_quantity' => [
                Rule::requiredIf(in_array($type, ['buy_x_get_y', 'buy_x_get_discount', 'bundle'])),
                'nullable', 'integer', 'min:1',
            ],
            'get_quantity' => [
                Rule::requiredIf($type === 'buy_x_get_y'),
                'nullable', 'integer', 'min:1',
            ],

            // min_amount
            'min_amount' => [
                Rule::requiredIf(in_array($type, ['buy_amount_get_discount', 'free_shipping'])),
                'nullable', 'numeric', 'min:0',
            ],

            // discount values (at least one expected for discount types)
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount'     => 'nullable|numeric|min:0',

            // tiered thresholds JSON string
            'tier_thresholds' => [
                Rule::requiredIf($type === 'tiered'),
                'nullable', 'string',
            ],

            // flash sale quantity
            'flash_quantity' => [
                'required_if:type,flash',
                'nullable', 'integer', 'min:1',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'                => 'اسم العرض',
            'type'                => 'نوع العرض',
            'applies_to'          => 'يُطبَّق على',
            'start_date'          => 'تاريخ البدء',
            'end_date'            => 'تاريخ الانتهاء',
            'value'               => 'القيمة',
            'buy_quantity'        => 'كمية الشراء',
            'get_quantity'        => 'كمية الهدية',
            'min_amount'          => 'الحد الأدنى للمبلغ',
            'discount_percentage' => 'نسبة الخصم',
            'discount_amount'     => 'مبلغ الخصم',
            'tier_thresholds'     => 'شرائح الخصم',
            'flash_quantity'      => 'الكمية المتاحة',
            'max_uses'            => 'الحد الأقصى للاستخدام',
            'priority'            => 'الأولوية',
            'description'         => 'الوصف',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique'                 => 'يوجد عرض بهذا الاسم بالفعل.',
            'end_date.after_or_equal'     => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء أو مساوياً له.',
            'value.required_if'           => 'القيمة مطلوبة لهذا النوع من العروض.',
            'buy_quantity.required_if'    => 'كمية الشراء مطلوبة.',
            'get_quantity.required_if'    => 'كمية الهدية مطلوبة.',
            'min_amount.required_if'      => 'الحد الأدنى للمبلغ مطلوب.',
            'tier_thresholds.required_if' => 'شرائح الخصم مطلوبة للخصم المتدرج.',
            'flash_quantity.required_if'  => 'الكمية المتاحة مطلوبة للعرض السريع.',
        ];
    }
}
