<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('cascade');
            $table->enum('type', ['in', 'out', 'transfer_in', 'transfer_out', 'adjustment'])
                ->comment('وارد / صادر / تحويل وارد / تحويل صادر / تعديل');
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 3)->default(0)->comment('الرصيد بعد الحركة');
            // المصدر: Order / Purchase / InventoryTransfer ...
            $table->nullableMorphs('reference');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id']);
            $table->index(['variant_id', 'warehouse_id']);
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_transactions');
    }
};
