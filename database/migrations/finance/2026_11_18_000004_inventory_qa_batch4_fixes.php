<?php

use App\Models\Finance\CustomerWallet;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\SupplierWallet;
use App\Services\Finance\ManualJournalService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * QA Batch 4 (المنتجات والمخزون والمحافظ) — سيناريوهات 2.8، 2.9، 4.4، 4.7، 5.2، 5.10
 *
 *  1. products.calories (اختياري)
 *  2. توحيد سجل الحركات: إكمال الصفوف الناقصة بين inventory_transactions و warehouse_transactions
 *  3. إعادة حساب متوسط التكلفة لكل منتج/متغير من الحركات بالترتيب الزمني
 *  4. قيد تسوية بفرق قيمة المخزون الفعلي عن حساب 1103 (مقابل 3001 رأس المال)
 *  5. قيد لكل حركة محفظة يدوية قديمة بلا قيد (مقابل 3001 رأس المال)
 */
return new class extends Migration
{
    private const COUNTER = '3001';
    private const REF     = 'qa_batch4_migration';

    public function up(): void
    {
        if (! Schema::hasColumn('products', 'calories')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('calories', 10, 2)->nullable()->after('alert_quantity');
            });
        }

        DB::transaction(function () {
            $this->unifyMovementLogs();
            $this->replayAverageCosts();
            $this->postInventoryDifference();
            $this->postManualWallets();
        });
    }

    /**
     * الحركة نفسها في الجدولين: نفس المستودع/المنتج/المتغير/الاتجاه/الكمية وخلال 5 ثوانٍ
     */
    private function unifyMovementLogs(): void
    {
        $dir  = fn (string $t) => in_array($t, ['in', 'transfer_in'], true) ? 'in' : (in_array($t, ['out', 'transfer_out'], true) ? 'out' : 'adjustment');
        $key  = fn ($r, string $type) => implode('|', [$r->warehouse_id, $r->product_id, $r->variant_id ?? 0, $dir($type), number_format((float) $r->quantity, 3, '.', '')]);
        $near = fn ($a, $b) => abs(strtotime((string) $a) - strtotime((string) $b)) <= 5;

        $inv = DB::table('inventory_transactions')->orderBy('id')->get();
        $wh  = DB::table('warehouse_transactions')->orderBy('id')->get();

        $whPool = [];
        foreach ($wh as $w) {
            $whPool[$key($w, $w->type)][] = $w;
        }

        $matchedWh = [];
        foreach ($inv as $i) {
            $k     = $key($i, $i->type);
            $found = null;
            foreach ($whPool[$k] ?? [] as $idx => $w) {
                if (! isset($matchedWh[$w->id]) && $near($i->created_at, $w->created_at)) {
                    $found = $w;
                    break;
                }
            }

            if ($found) {
                $matchedWh[$found->id] = true;
                continue;
            }

            // حركة في سجل المخزون فقط (شاشة تعديل المخزون القديمة) → تُضاف لسجل حركات المستودع
            DB::table('warehouse_transactions')->insert([
                'warehouse_id'  => $i->warehouse_id,
                'product_id'    => $i->product_id,
                'variant_id'    => $i->variant_id,
                'unit_id'       => DB::table('products')->where('id', $i->product_id)->value('unit_id'),
                'type'          => $i->type,
                'quantity'      => $i->quantity,
                'unit_cost'     => $i->unit_cost,
                'balance_after' => 0,
                'notes'         => trim(($i->notes ?? '') . ' (مُكمَّل من سجل المخزون — QA الدفعة 4)'),
                'created_by'    => $i->created_by,
                'created_at'    => $i->created_at,
                'updated_at'    => $i->updated_at,
            ]);
        }

        foreach ($wh as $w) {
            if (isset($matchedWh[$w->id])) {
                continue;
            }

            // حركة في سجل المستودع فقط (شاشة حركات المخزون القديمة) → تُضاف لسجل المخزون
            DB::table('inventory_transactions')->insert([
                'warehouse_id' => $w->warehouse_id,
                'product_id'   => $w->product_id,
                'variant_id'   => $w->variant_id,
                'type'         => $dir($w->type),
                'quantity'     => $w->quantity,
                'unit_cost'    => $w->unit_cost,
                'notes'        => trim(($w->notes ?? '') . ' (مُكمَّل من سجل حركات المستودع — QA الدفعة 4)'),
                'created_by'   => $w->created_by,
                'created_at'   => $w->created_at,
                'updated_at'   => $w->updated_at,
            ]);
        }
    }

    /**
     * متوسط مرجّح متحرك: الوارد بتكلفة يحدّث المتوسط، الوارد بلا تكلفة (تحويل) والصادر يغيّران الكمية فقط
     */
    private function replayAverageCosts(): void
    {
        $rows = DB::table('inventory_transactions')->orderBy('created_at')->orderBy('id')->get()
            ->groupBy(fn ($r) => $r->product_id . '|' . ($r->variant_id ?? 0));

        foreach ($rows as $group => $moves) {
            [$productId, $variantId] = array_map('intval', explode('|', $group));
            $qty = 0.0;
            $avg = 0.0;

            foreach ($moves as $m) {
                $q = (float) $m->quantity;
                $c = (float) $m->unit_cost;

                if ($m->type === 'in') {
                    if ($c > 0) {
                        $base = $avg > 0 ? $avg : $c;
                        $avg  = ($qty + $q) > 0 ? (($qty * $base) + ($q * $c)) / ($qty + $q) : $c;
                    }
                    $qty += $q;
                } elseif ($m->type === 'out') {
                    $qty = max(0.0, $qty - $q);
                }
            }

            if ($avg <= 0) {
                continue;
            }

            $variantId
                ? DB::table('product_variants')->where('id', $variantId)->update(['average_cost' => round($avg, 2)])
                : DB::table('products')->where('id', $productId)->update(['average_cost' => round($avg, 2)]);
        }
    }

    private function postInventoryDifference(): void
    {
        $inventory = DB::table('account_trees')->where('code', '1103')->first();
        $counter   = DB::table('account_trees')->where('code', self::COUNTER)->value('id');
        if (! $inventory || ! $counter) {
            return;
        }

        $value = (float) DB::table('inventory_items as i')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('product_variants as v', 'v.id', '=', 'i.variant_id')
            ->selectRaw('COALESCE(SUM(i.quantity * CASE WHEN i.variant_id IS NULL THEN p.average_cost ELSE v.average_cost END), 0) as v')
            ->value('v');

        $diff = round($value - (float) $inventory->balance, 2);
        if (abs($diff) < 0.01) {
            return;
        }

        $this->post('opening_balance', 'تسوية رصيد المخزون مع قيمته الفعلية (أرصدة افتتاحية وحركات يدوية قديمة بلا قيد) — QA الدفعة 4', [
            ['account_tree_id' => $inventory->id, 'debit' => max($diff, 0), 'credit' => max(-$diff, 0)],
            ['account_tree_id' => $counter,       'debit' => max(-$diff, 0), 'credit' => max($diff, 0)],
        ], self::REF, null);
    }

    private function postManualWallets(): void
    {
        $counter = DB::table('account_trees')->where('code', self::COUNTER)->value('id');
        if (! $counter) {
            return;
        }

        $sets = [
            [CustomerWallet::class, '1104', CustomerWallet::whereNull('voucher_id')->whereNull('order_id')->get(), 'customer'],
            [SupplierWallet::class, '2101', SupplierWallet::whereNull('voucher_id')->whereNull('purchase_id')->get(), 'supplier'],
        ];

        foreach ($sets as [$class, $code, $rows, $kind]) {
            $party = DB::table('account_trees')->where('code', $code)->value('id');
            foreach ($rows as $row) {
                if (! $party || JournalEntry::where('reference_type', $class)->where('reference_id', $row->id)->exists()) {
                    continue;
                }
                $d = round((float) $row->debit, 2);
                $c = round((float) $row->credit, 2);
                if ($d == 0.0 && $c == 0.0) {
                    continue;
                }

                $type = $kind === 'customer'
                    ? ($d > 0 ? 'customer_debit_note' : 'customer_credit_note')
                    : ($d > 0 ? 'supplier_debit_note' : 'supplier_credit_note');

                $this->post($type, 'حركة محفظة يدوية قديمة #' . $row->id . ' بلا قيد' . ($row->description ? ' — ' . $row->description : '') . ' (QA الدفعة 4)', [
                    ['account_tree_id' => $party,   'debit' => $d, 'credit' => $c],
                    ['account_tree_id' => $counter, 'debit' => $c, 'credit' => $d],
                ], $class, $row->id, $row->date);
            }
        }
    }

    private function post(string $type, string $description, array $lines, string $refType, ?int $refId, $date = null): void
    {
        $journal = app(ManualJournalService::class);

        $entry = JournalEntry::create([
            'entry_number'   => $journal->nextEntryNumber(),
            'entry_type'     => $type,
            'date'           => $date ?? now()->toDateString(),
            'description'    => $description,
            'reference_type' => $refType,
            'reference_id'   => $refId,
            'status'         => 'posted',
        ]);

        $journal->replaceItems($entry, $lines);
    }

    public function down(): void
    {
        // تصحيح بيانات: لا رجوع آلي (توجد نسخة احتياطية قبل الدفعة 4)
    }
};
