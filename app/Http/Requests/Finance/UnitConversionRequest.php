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

    /**
     * QA 3.3: لا يتكرر نفس التحويل (نفس المنتج/المتغير/الوحدتين)
     * QA 3.4: التحويل يتسق مع سلسلة التحويلات المسجلة (علبة→شريط × شريط→حبة = علبة→حبة)
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $id      = (int) $this->input('id');
            $base    = (int) $this->input('base_unit');
            $target  = (int) $this->input('target_unit');
            $rate    = (float) $this->input('conversion_rate');
            $product = $this->input('product_id') ?: null;
            $variant = $this->input('variant_id') ?: null;

            $existing = \App\Models\Finance\UnitConversion::query()
                ->when($product, fn ($q) => $q->where('product_id', $product), fn ($q) => $q->whereNull('product_id'))
                ->when($variant, fn ($q) => $q->where('variant_id', $variant), fn ($q) => $q->whereNull('variant_id'))
                ->when($id, fn ($q) => $q->where('id', '!=', $id))
                ->get();

            $duplicate = $existing->first(fn ($c) => (int) $c->base_unit === $base && (int) $c->target_unit === $target);
            if ($duplicate) {
                $validator->errors()->add('target_unit', 'تحويل الوحدة هذا موجود بالفعل (المعامل ' . (float) $duplicate->conversion_rate . ')، عدّله بدلاً من إضافته مرة أخرى');
                return;
            }

            // معامل ضمني من الوحدة الأساسية إلى الهدف عبر التحويلات الموجودة (في الاتجاهين)
            $edges = [];
            foreach ($existing as $c) {
                $r = (float) $c->conversion_rate;
                if ($r <= 0) {
                    continue;
                }
                $edges[(int) $c->base_unit][(int) $c->target_unit] = $r;
                $edges[(int) $c->target_unit][(int) $c->base_unit] = 1 / $r;
            }

            $implied = [$base => 1.0];
            $queue   = [$base];
            while ($queue) {
                $u = array_shift($queue);
                foreach ($edges[$u] ?? [] as $v => $r) {
                    if (! isset($implied[$v])) {
                        $implied[$v] = $implied[$u] * $r;
                        $queue[]     = $v;
                    }
                }
            }

            if (isset($implied[$target]) && abs($implied[$target] - $rate) > max(0.0001, 0.001 * $implied[$target])) {
                $validator->errors()->add('conversion_rate', 'المعامل لا يتسق مع التحويلات المسجلة لهذا المنتج: حسب السلسلة يجب أن يكون '
                    . rtrim(rtrim(number_format($implied[$target], 6, '.', ''), '0'), '.'));
            }
        });
    }
}
