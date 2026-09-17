<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_transactions', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('variant_id')
                ->constrained('units')->nullOnDelete()
                ->comment('وحدة قياس الكمية المسجلة (الوحدة الأساسية للمنتج)');
            $table->foreignId('unit_conversion_id')->nullable()->after('unit_id')
                ->constrained('unit_conversions')->nullOnDelete()
                ->comment('وحدة التحويل المستخدمة عند الإدخال (طلب / شراء) إن وجدت');

            $table->index('unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_transactions', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['unit_conversion_id']);
            $table->dropIndex(['unit_id']);
            $table->dropColumn(['unit_id', 'unit_conversion_id']);
        });
    }
};
