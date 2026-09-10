<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

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
            'warehouse_id'         => 'required|exists:warehouses,id',
            'date'                 => 'required|date',
            'due_date'             => 'nullable|date|after_or_equal:date',
            'payment_type'         => 'required|in:cash,credit,installments,wallet,bank_online,bank_direct,check',
            'status'               => 'required|in:pending,received,partially_received,cancelled',
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

    public function attributes(): array
    {
        return [
            'supplier_id'        => 'المورد',
            'warehouse_id'       => 'المستودع',
            'date'               => 'التاريخ',
            'due_date'           => 'تاريخ الاستحقاق',
            'payment_type'       => 'نوع الدفع',
            'status'             => 'الحالة',
            'notes'              => 'الملاحظات',
            'paid'               => 'المدفوع',
            'items'              => 'بنود الفاتورة',
            'items.*.product_id' => 'المنتج',
            'items.*.quantity'   => 'الكمية',
            'items.*.unit_cost'  => 'سعر الوحدة',
            'items.*.discount'   => 'الخصم',
            'items.*.tax_rate'   => 'نسبة الضريبة',
        ];
    }
}
