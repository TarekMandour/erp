<?php

namespace App\Http\Requests\Finance;

use App\Models\Finance\AccountTree;
use App\Models\Finance\JournalEntryItem;
use Illuminate\Foundation\Http\FormRequest;

class AccountTreeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->id;

        return [
            'name'      => 'required|string|max:255',
            'code'      => 'required|string|max:100|unique:account_trees,code,' . $id,
            'parent_id' => 'nullable|exists:account_trees,id',
            'account_type' => 'nullable|in:debit,credit',
            'type'      => 'required|in:asset,liability,equity,revenue,expense',
        ];
    }

    /**
     * اتساق الشجرة: لا أب ذاتي ولا حلقات، النوع يطابق الأب، الكود يبدأ ببادئة الأب،
     * الأب لا يحمل قيوداً، ولا يُغيَّر نوع حساب له فروع من نوع آخر.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $id       = $this->route('id') ?? $this->id;
            $parentId = $this->input('parent_id');
            $type     = $this->input('type');
            $code     = (string) $this->input('code');

            if ($parentId) {
                if ($id && (int) $parentId === (int) $id) {
                    $validator->errors()->add('parent_id', 'لا يمكن أن يكون الحساب أباً لنفسه');
                    return;
                }

                // الصعود من الأب المختار: إن وصلنا للحساب نفسه فهي حلقة
                $cursor  = AccountTree::find($parentId);
                $visited = [];
                while ($cursor && ! isset($visited[$cursor->id])) {
                    if ($id && (int) $cursor->id === (int) $id) {
                        $validator->errors()->add('parent_id', 'لا يمكن نقل الحساب تحت أحد فروعه (حلقة في الشجرة)');
                        return;
                    }
                    $visited[$cursor->id] = true;
                    $cursor = $cursor->parent_id ? AccountTree::find($cursor->parent_id) : null;
                }

                $parent = AccountTree::find($parentId);

                if ($parent->type !== $type) {
                    $validator->errors()->add('type', 'نوع الحساب يجب أن يطابق نوع الحساب الأب (' . $parent->type_name . ')');
                }

                $prefix = rtrim((string) $parent->code, '0');
                if ($prefix !== '' && ! str_starts_with($code, $prefix)) {
                    $validator->errors()->add('code', "كود الحساب يجب أن يبدأ ببادئة الحساب الأب ({$prefix})");
                }

                if ($code === (string) $parent->code) {
                    $validator->errors()->add('code', 'كود الحساب يجب أن يختلف عن كود الأب');
                }

                $isSameParent = $id && (int) AccountTree::where('id', $id)->value('parent_id') === (int) $parentId;
                // تحويل حساب فرعي عليه قيود إلى حساب أب يجعل قيوده على حساب رئيسي
                $parentIsLeaf = ! AccountTree::where('parent_id', $parentId)->where('id', '!=', $id ?: 0)->exists();
                if (! $isSameParent && $parentIsLeaf && JournalEntryItem::where('account_tree_id', $parentId)->exists()) {
                    $validator->errors()->add('parent_id', 'الحساب المختار حساب فرعي عليه قيود، لا يمكن تحويله إلى حساب أب');
                }
            }

            if ($id && AccountTree::where('parent_id', $id)->where('type', '!=', $type)->exists()) {
                $validator->errors()->add('type', 'لا يمكن تغيير نوع حساب له فروع من نوع مختلف');
            }
        });
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'كود الحساب مستخدم من قبل',
        ];
    }
}
