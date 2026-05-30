<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->json('attributes')->nullable()->comment('خصائص المنتج مثل اللون والمقاس');
            $table->decimal('purchase_price', 15, 2)->default(0)->comment('سعر الشراء لهذا النوع');
            $table->decimal('selling_price', 15, 2)->default(0)->comment('سعر البيع لهذا النوع');
            $table->decimal('cost_price', 15, 2)->default(0)->comment('التكلفة الفعلية بعد الخصم والضرائب');
            $table->decimal('average_cost', 15, 2)->default(0)->comment('متوسط تكلفة المنتج من جميع الأنواع');
            $table->integer('alert_quantity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false)->comment('النوع الافتراضي عند اختيار المنتج');
            $table->integer('sort_order')->default(0);
            $table->decimal('weight', 10, 3)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'is_active']);
            $table->index('sku');
            $table->index('barcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
