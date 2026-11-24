<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * مرتجعات المبيعات: مستند مستقل يُنشأ من طلب بيع أصلي.
 *
 *  - sales_returns / sales_return_items
 *  - customer_wallets.sales_return_id: صفوف المحفظة تُربط بالمرتجع (وليس order_id)
 *    حتى لا يحذفها عكس الطلب الأصلي
 *  - تفعيل سيناريوهات ORDER_RETURN_* (كانت معطلة في الدفعة 6 حتى تُبنى هذه الشاشة)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sales_returns')) {
            Schema::create('sales_returns', function (Blueprint $table) {
                $table->id();
                $table->string('return_number', 50)->unique();
                $table->foreignId('order_id')->constrained('orders');
                $table->foreignId('customer_id')->constrained('customers');
                $table->foreignId('warehouse_id')->constrained('warehouses');
                $table->date('date');
                $table->enum('payment_type', ['cash', 'credit', 'installments', 'wallet', 'bank_online', 'bank_direct', 'check'])->default('cash');
                $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
                $table->enum('status', ['confirmed', 'cancelled'])->default('confirmed');
                $table->boolean('is_full')->default(false)->comment('يكمل إرجاع كل كميات الطلب');
                $table->boolean('include_shipping')->default(false);
                $table->text('notes')->nullable();
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('discount', 12, 2)->default(0);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('shipping_cost', 12, 2)->default(0);
                $table->decimal('coupon_discount', 12, 2)->default(0);
                $table->decimal('offer_discount', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->decimal('paid', 12, 2)->default(0)->comment('المردود نقداً/بنكياً فعلاً');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sales_return_items')) {
            Schema::create('sales_return_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sales_return_id')->constrained('sales_returns')->cascadeOnDelete();
                $table->foreignId('order_item_id')->constrained('order_items');
                $table->foreignId('product_id')->constrained('products');
                $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
                $table->foreignId('unit_conversion_id')->nullable()->constrained('unit_conversions')->nullOnDelete();
                $table->decimal('quantity', 12, 3);
                $table->decimal('unit_price', 12, 2);
                $table->decimal('unit_cost', 12, 4)->default(0);
                $table->decimal('gross', 12, 2)->default(0);
                $table->decimal('discount', 12, 2)->default(0);
                $table->decimal('invoice_discount', 12, 2)->default(0);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('customer_wallets', 'sales_return_id')) {
            Schema::table('customer_wallets', function (Blueprint $table) {
                $table->unsignedBigInteger('sales_return_id')->nullable()->after('order_id')->index();
            });
        }

        DB::table('posting_scenarios')->where('code', 'like', 'ORDER_RETURN_%')->update(['is_active' => 1]);
        foreach (DB::table('posting_scenarios')->where('code', 'like', 'ORDER_RETURN_%')->get(['id', 'code']) as $s) {
            Cache::forget("posting_scenario:{$s->code}");
            Cache::forget("posting_rules:{$s->id}");
        }
    }

    public function down(): void
    {
        DB::table('posting_scenarios')->where('code', 'like', 'ORDER_RETURN_%')->update(['is_active' => 0]);

        if (Schema::hasColumn('customer_wallets', 'sales_return_id')) {
            Schema::table('customer_wallets', fn (Blueprint $table) => $table->dropColumn('sales_return_id'));
        }
        Schema::dropIfExists('sales_return_items');
        Schema::dropIfExists('sales_returns');
    }
};
