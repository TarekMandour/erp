<?php

namespace App\Http\Requests\Finance;

use App\Helpers\Helper;
use App\Models\Finance\Product;
use App\Models\Finance\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id'          => 'required|exists:suppliers,id',
            'warehouse_id'         => ['required', Rule::exists('warehouses', 'id')->where('is_active', 1)],
            'date'                 => 'required|date',
            'due_date'             => 'nullable|date|after_or_equal:date',
            'payment_type'         => 'required|in:cash,credit,installments,wallet,bank_online,bank_direct,check',
            'bank_account_id'      => 'nullable|exists:bank_accounts,id',
            'status'               => 'required|in:pending,received,cancelled',
            'notes'                => 'nullable|string|max:1000',
            'paid'                 => 'nullable|numeric|min:0',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.variant_id'   => 'nullable|exists:product_variants,id',
            'items.*.quantity'     => 'required|numeric|min:0.001',
            'items.*.unit_cost'    => 'required|numeric|min:0',
            'items.*.discount'     => 'nullable|numeric|min:0',
            'items.*.tax_rate'     => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $items = (array) $this->input('items', []);

            // المتغيّر إلزامي لمنتج له متغيّرات، ويجب أن يتبع المنتج نفسه
            foreach ($items as $i => $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                $variantId = $item['variant_id'] ?? null;

                if ($variantId) {
                    $belongs = ProductVariant::where('id', $variantId)->where('product_id', $productId)->exists();
                    if (! $belongs) {
                        $v->errors()->add("items.$i.variant_id", 'المتغيّر المختار لا يتبع المنتج في السطر ' . ($i + 1) . '.');
                    }
                } elseif (Product::find($productId)?->variants()->exists()) {
                    $v->errors()->add("items.$i.variant_id", 'يجب اختيار المتغيّر للمنتج في السطر ' . ($i + 1) . ' لأن له متغيّرات.');
                }
            }

            // المدفوع لا يتجاوز إجمالي الفاتورة
            $total = $this->computedTotal($items);
            if ((float) $this->input('paid', 0) > $total + 0.005) {
                $v->errors()->add('paid', 'المدفوع لا يمكن أن يتجاوز إجمالي الفاتورة (' . number_format($total, 2) . ').');
            }
        });
    }

    /**
     * نفس معادلة الإجمالي في PurchasesController::recalcTotals
     */
    private function computedTotal(array $items): float
    {
        $inclusive = Helper::pricingMode() === 'inclusive';
        $total     = 0.0;

        foreach ($items as $item) {
            $net  = (float) ($item['quantity'] ?? 0) * (float) ($item['unit_cost'] ?? 0) - (float) ($item['discount'] ?? 0);
            $rate = (float) ($item['tax_rate'] ?? 0);
            $total += $inclusive ? $net : $net + round($net * $rate / 100, 2);
        }

        return round($total, 2);
    }

    public function attributes(): array
    {
        return [
            'supplier_id'        => 'المورد',
            'warehouse_id'       => 'المستودع',
            'date'               => 'التاريخ',
            'due_date'           => 'تاريخ الاستحقاق',
            'payment_type'       => 'نوع الدفع',
            'bank_account_id'    => 'الحساب البنكي',
            'status'             => 'الحالة',
            'notes'              => 'الملاحظات',
            'paid'               => 'المدفوع',
            'items'              => 'بنود الفاتورة',
            'items.*.product_id' => 'المنتج',
            'items.*.variant_id' => 'المتغيّر',
            'items.*.quantity'   => 'الكمية',
            'items.*.unit_cost'  => 'سعر الوحدة',
            'items.*.discount'   => 'الخصم',
            'items.*.tax_rate'   => 'نسبة الضريبة',
        ];
    }
}
