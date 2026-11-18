<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'          => 'required|exists:customers,id',
            'warehouse_id'         => 'required|exists:warehouses,id',
            'date'                 => 'required|date',
            'delivery_date'        => 'nullable|date|after_or_equal:date',
            'payment_type'         => 'required|in:cash,credit,installments,wallet,bank_online,bank_direct,check',
            'bank_account_id'      => 'nullable|exists:bank_accounts,id',
            'coupon_id'            => 'nullable|exists:coupons,id',
            'offer_id'             => 'nullable|exists:offers,id',
            'status'               => 'required|in:draft,confirmed,processing,shipped,delivered,cancelled',
            'notes'                => 'nullable|string|max:1000',
            'shipping_address'     => 'nullable|string|max:500',
            'shipping_cost'        => 'nullable|numeric|min:0',
            'paid'                 => 'nullable|numeric|min:0',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.variant_id'   => 'nullable|exists:product_variants,id',
            'items.*.unit_conversion_id' => 'nullable|exists:unit_conversions,id',
            'items.*.quantity'     => 'required|numeric|min:0.001',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.unit_cost'    => 'nullable|numeric|min:0',
            'items.*.discount'     => 'nullable|numeric|min:0',
            'items.*.tax_rate'     => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id'        => 'العميل',
            'warehouse_id'       => 'المستودع',
            'date'               => 'التاريخ',
            'delivery_date'      => 'تاريخ التسليم',
            'payment_type'       => 'طريقة الدفع',
            'status'             => 'الحالة',
            'notes'              => 'الملاحظات',
            'shipping_address'   => 'عنوان الشحن',
            'shipping_cost'      => 'تكلفة الشحن',
            'paid'               => 'المدفوع',
            'bank_account_id'    => 'الحساب البنكي',
            'coupon_id'          => 'الكوبون',
            'offer_id'           => 'العرض',
            'items.*.unit_conversion_id' => 'وحدة البيع',
            'items.*.variant_id' => 'المتغير',
            'items'              => 'بنود الطلب',
            'items.*.product_id' => 'المنتج',
            'items.*.quantity'   => 'الكمية',
            'items.*.unit_price' => 'سعر الوحدة',
            'items.*.discount'   => 'الخصم',
            'items.*.tax_rate'   => 'نسبة الضريبة',
        ];
    }
}
