<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Expand the type enum from 6 → 11 offer types
        DB::statement("ALTER TABLE `offers` MODIFY COLUMN `type` ENUM(
            'percentage',
            'fixed',
            'buy_x_get_y',
            'buy_x_get_discount',
            'buy_amount_get_discount',
            'product_price_discount',
            'tiered',
            'flash',
            'free_shipping',
            'bundle',
            'first_order'
        ) NOT NULL");

        Schema::table('offers', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->unsignedInteger('priority')->default(0)->after('is_active');
            $table->unsignedInteger('max_uses')->nullable()->after('priority');
            $table->unsignedInteger('used_count')->default(0)->after('max_uses');
            $table->boolean('is_stackable')->default(false)->after('used_count');
            $table->json('tier_thresholds')->nullable()->after('is_stackable');
            $table->unsignedInteger('flash_quantity')->nullable()->after('tier_thresholds');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'priority',
                'max_uses',
                'used_count',
                'is_stackable',
                'tier_thresholds',
                'flash_quantity',
            ]);
        });

        DB::statement("ALTER TABLE `offers` MODIFY COLUMN `type` ENUM(
            'percentage',
            'fixed',
            'buy_x_get_y',
            'buy_x_get_discount',
            'buy_amount_get_discount',
            'product_price_discount'
        ) NOT NULL");
    }
};
