<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class BankAccountRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->id;
        return [
            'bank_id'        => 'required|exists:banks,id',
            'account_tree_id'        => 'required|exists:account_trees,id',
            'account_number' => 'required|string|max:100|unique:bank_accounts,account_number,' . $id,
            'currency'       => 'required|string|max:10',
        ];
    }

    public function attributes(): array
    {
        return [
            'bank_id'         => 'البنك',
            'account_tree_id' => 'الحساب في الشجرة',
            'account_number'  => 'رقم الحساب',
            'currency'        => 'العملة',
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

            $id = $this->route('id') ?? $this->id;
            $usedByTreasury = \App\Models\Finance\Treasury::where('account_tree_id', $accountId)->exists();
            $usedByBank     = \App\Models\Finance\BankAccount::where('account_tree_id', $accountId)->when($id, fn ($q) => $q->where('id', '!=', $id))->exists();

            if ($usedByTreasury || $usedByBank) {
                $validator->errors()->add('account_tree_id', 'هذا الحساب مربوط بالفعل ب' . ($usedByTreasury ? 'خزنة أخرى' : 'حساب بنكي آخر'));
            }
        });
    }
}
