<?php

namespace Tests\Feature\Finance;

use App\Models\Admin;
use App\Models\Finance\AccountTree;
use App\Models\Finance\JournalEntryItem;
use Database\Seeders\ClothesShop\AccountTreeSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * قاعدة مشتركة لاختبارات QA المالية.
 * تعمل على قاعدة MySQL منفصلة (erp_testing من phpunit.xml) لأن migrations المشروع MySQL فقط.
 * تُرحَّل القاعدة مرة واحدة لكل تشغيل: المجلد الأساسي ثم finance (نفس ترتيب الإنتاج)،
 * وكل اختبار داخل معاملة تُلغى في نهايته.
 */
abstract class FinanceTestCase extends TestCase
{
    use DatabaseTransactions;

    private static bool $migrated = false;

    protected function setUpTraits()
    {
        if (! self::$migrated) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            Artisan::call('migrate', ['--path' => 'database/migrations/finance', '--force' => true]);
            self::$migrated = true;
        }

        return parent::setUpTraits();
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(AccountTreeSeeder::class);

        $admin = Admin::create(['name' => 'QA', 'email' => 'qa@test.local', 'phone' => '0100', 'password' => bcrypt('x'), 'is_active' => '1']);
        $this->actingAs($admin, 'admin');
    }

    protected function runMigrationUp(string $file): void
    {
        (require base_path('database/migrations/finance/' . $file))->up();
        Cache::flush();
    }

    protected function acc(string $code): AccountTree
    {
        return AccountTree::where('code', $code)->firstOrFail();
    }

    /**
     * رصيد الحساب (مدين − دائن) كما في شجرة الحسابات، مع التحقق من مطابقته لبنود القيود غير المسودة
     */
    protected function ledger(string $code): float
    {
        $account   = $this->acc($code);
        $fromItems = round((float) JournalEntryItem::whereHas('entry', fn ($q) => $q->where('status', '!=', 'draft'))
            ->where('account_tree_id', $account->id)
            ->selectRaw('COALESCE(SUM(debit - credit),0) as b')->value('b'), 2);

        $this->assertEquals($fromItems, (float) $account->balance, "رصيد الشجرة {$code} لا يطابق بنود القيود");

        return (float) $account->balance;
    }
}
