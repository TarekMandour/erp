<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class TreasuryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->input('id');

        return [
            'name'     => 'required|string|max:255|unique:treasuries,name,' . ($id ?? 'NULL') . ',id',
            'account_tree_id'        => 'required|exists:account_trees,id',
            'currency' => 'required|string|max:10',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'     => 'اسم الخزنة',
            'account_tree_id' => 'شجرة الحساب',
            'currency' => 'العملة',
        ];
    }

    /**
     * الحساب المربوط: إلزامي، أصول فرعي نشط، ولا يُربط بخزنة أو حساب بنكي آخر
     * (JournalEntryItemObserver يحدد الخزنة/البنك من الحساب، فالربط المزدوج غامض).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $accountId = $this->input('account_tree_id');
            if ($validator->errors()->has('account_tree_id') || ! $accountId) {
                return;
            }

            $account = \App\Models\Finance\AccountTree::withCount('children')->find($accountId);
            if (! $account || $account->type !== 'asset' || $account->children_count > 0 || ! $account->is_active) {
                $validator->errors()->add('account_tree_id', 'اختر حساب أصول فرعياً نشطاً (مثل الصندوق أو البنك)');
                return;
            }

            // QA 7.2: الخزنة تُربط بحساب نقدية فقط (لا المخزون ولا العملاء ولا البنك)
            if (! $account->is_cash) {
                $validator->errors()->add('account_tree_id', 'الحساب «' . $account->code . ' - ' . $account->name . '» ليس حساب نقدية. فعّل «حساب نقدية» له في شجرة الحسابات أو اختر حساب الصندوق.');
                return;
            }

            $id = $this->input('id');
            $usedByTreasury = \App\Models\Finance\Treasury::where('account_tree_id', $accountId)->when($id, fn ($q) => $q->where('id', '!=', $id))->exists();
            $usedByBank     = \App\Models\Finance\BankAccount::where('account_tree_id', $accountId)->exists();

            if ($usedByTreasury || $usedByBank) {
                $validator->errors()->add('account_tree_id', 'هذا الحساب مربوط بالفعل ب' . ($usedByTreasury ? 'خزنة أخرى' : 'حساب بنكي آخر'));
            }
        });
    }
}
