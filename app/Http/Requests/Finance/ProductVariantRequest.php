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

    /**
     * الخصائص من مكرر النموذج (kt_docs_repeater_basic) بعد تجاهل الصفوف الفارغة
     */
    public function variantAttributes(): array
    {
        $rows = collect((array) $this->input('kt_docs_repeater_basic', []))
            ->filter(fn ($r) => isset($r['attri_keys'], $r['attri_values']) && $r['attri_keys'] !== '' && trim((string) $r['attri_values']) !== '');

        return $rows->mapWithKeys(fn ($r) => [(string) $r['attri_keys'] => trim((string) $r['attri_values'])])->all();
    }

    /**
     * QA 2.11 / 2.12: المنتج يجب أن يكون ذا متغيرات، وللمتغير خاصية واحدة على الأقل، ولا تتكرر التركيبة
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $productId = (int) $this->route('productId');
            $product   = \App\Models\Finance\Product::find($productId);

            if (! $product) {
                $validator->errors()->add('product_id', 'المنتج غير موجود');
                return;
            }
            if (! $product->has_variants) {
                $validator->errors()->add('attributes', 'هذا المنتج ليس له متغيرات (has_variants غير مفعّل)، فعّل الخيار من شاشة المنتج أولاً');
                return;
            }

            $attrs = $this->variantAttributes();
            if (empty($attrs)) {
                $validator->errors()->add('attributes', 'يجب تحديد خاصية واحدة على الأقل للمتغير (مثل اللون أو المقاس)');
                return;
            }

            $normalize = function ($a) {
                $a = array_map(fn ($v) => mb_strtolower(trim((string) $v)), (array) $a);
                ksort($a);
                return $a;
            };
            $wanted = $normalize($attrs);

            $duplicate = \App\Models\Finance\ProductVariant::where('product_id', $productId)
                ->when($this->input('id'), fn ($q) => $q->where('id', '!=', $this->input('id')))
                ->get()
                ->first(fn ($v) => $normalize($v->attributes ?? []) == $wanted);

            if ($duplicate) {
                $validator->errors()->add('attributes', 'تركيبة الخصائص هذه موجودة بالفعل في المتغير ' . $duplicate->sku);
            }
        });
    }

    public function attributes(): array
    {
        return [
            'sku' => 'SKU', 'barcode' => 'الباركود', 'purchase_price' => 'سعر الشراء', 'selling_price' => 'سعر البيع',
            'cost_price' => 'سعر التكلفة', 'average_cost' => 'متوسط التكلفة',
        ];
    }
}
