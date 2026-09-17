<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transfers', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('variant_id')
                ->constrained('units')->nullOnDelete()
                ->comment('الوحدة الأساسية للمنتج وقت التحويل');
            $table->foreignId('unit_conversion_id')->nullable()->after('unit_id')
                ->constrained('unit_conversions')->nullOnDelete()
                ->comment('وحدة التحويل المستخدمة عند إدخال الكمية إن وجدت');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transfers', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['unit_conversion_id']);
            $table->dropColumn(['unit_id', 'unit_conversion_id']);
        });
    }
};
