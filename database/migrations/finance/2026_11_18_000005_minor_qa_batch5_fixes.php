<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * QA Batch 5 (أخطاء بسيطة) — سيناريوهات 1.6، 2.3، 2.4
 *
 *  1. الفئة التي هي أب لنفسها → بلا أب
 *  2. الفئات الفرعية تحت أب محذوف → فئات رئيسية
 *  3. تفعيل المدير الأساسي (id = 1) قبل تفعيل منع دخول المدير غير المفعّل
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            DB::table('categories')->whereColumn('parent_id', 'id')->update(['parent_id' => null]);

            $deleted = DB::table('categories')->whereNotNull('deleted_at')->pluck('id');
            DB::table('categories')->whereNull('deleted_at')->whereIn('parent_id', $deleted)->update(['parent_id' => null]);

            DB::table('admins')->where('id', 1)->where('is_active', '!=', '1')->update(['is_active' => '1']);
        });
    }

    public function down(): void
    {
        // تصحيح بيانات: لا رجوع آلي (توجد نسخة احتياطية قبل الدفعة 5)
    }
};
