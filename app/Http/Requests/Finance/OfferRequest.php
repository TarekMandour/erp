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

    /**
     * QA 6: قيود كل نوع عرض حتى لا يُحفظ عرض يعطي خصماً صفرياً أو أكبر من الطلب
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type  = $this->input('type');
            $value = (float) $this->input('value');
            $err   = fn (string $f, string $m) => $validator->errors()->add($f, $m);

            if (in_array($type, ['percentage', 'first_order'], true) && ($value <= 0 || $value > 100)) {
                $err('value', 'نسبة الخصم يجب أن تكون أكبر من 0 ولا تتجاوز 100%.');
            }
            if (in_array($type, ['fixed', 'product_price_discount', 'bundle'], true) && $value <= 0) {
                $err('value', 'القيمة يجب أن تكون أكبر من صفر.');
            }
            if (in_array($type, ['buy_x_get_discount', 'buy_amount_get_discount', 'flash'], true)
                && (float) $this->input('discount_percentage') <= 0 && (float) $this->input('discount_amount') <= 0) {
                $err('discount_percentage', 'حدد نسبة الخصم أو مبلغ الخصم لهذا العرض.');
            }
            if (in_array($this->input('applies_to'), ['product', 'category'], true)
                && empty(array_filter((array) $this->input('item_ids', [])))) {
                $err('item_ids', $this->input('applies_to') === 'product' ? 'اختر المنتجات المشمولة بالعرض.' : 'اختر الفئات المشمولة بالعرض.');
            }
            if ($type === 'free_shipping' && $this->input('applies_to') !== 'order') {
                $err('applies_to', 'الشحن المجاني يُطبَّق على الطلب كاملاً.');
            }
            if ($type === 'tiered') {
                $tiers = json_decode((string) $this->input('tier_thresholds'), true);
                $valid = is_array($tiers) && count($tiers) > 0;
                foreach ((array) $tiers as $t) {
                    $min = $t['min_amount'] ?? $t['min'] ?? null;
                    $pct = $t['discount_percentage'] ?? $t['discount'] ?? null;
                    if (! is_numeric($min) || $min < 0 || ! is_numeric($pct) || $pct <= 0 || $pct > 100) {
                        $valid = false;
                    }
                }
                if (! $valid) {
                    $err('tier_thresholds', 'أضف شريحة واحدة على الأقل: حد أدنى ≥ 0 ونسبة بين 0 و100.');
                }
            }
        });
    }
}
