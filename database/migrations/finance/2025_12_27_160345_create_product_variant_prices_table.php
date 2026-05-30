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
        Schema::create('product_variant_prices', function (Blueprint $table) {
            $table->id();
            
            // المنتج والنوع
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->unsignedBigInteger('variant_id')->nullable();
            
            // التسعير
            $table->decimal('price', 15, 2)->comment('السعر الأساسي');
            $table->decimal('sale_price', 15, 2)->nullable()->comment('سعر التخفيض');
            $table->date('sale_start')->nullable();
            $table->date('sale_end')->nullable();
            
            // تسعير حسب الكمية
            $table->json('quantity_prices')->nullable()->comment('أسعار حسب الكمية');
            
            // تسعير خاص للعملاء
            $table->foreignId('customer_id')->nullable()->constrained();
            $table->json('customer_ids')->nullable()->comment('قائمة معرفات العملاء');
            $table->foreignId('customer_group_id')->nullable()->comment('مجموعة العملاء');
            
            // صلاحية السعر
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0)->comment('أولوية التطبيق');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['variant_id', 'is_active']);
            $table->index(['variant_id', 'customer_id']);
            $table->index(['valid_from', 'valid_to']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variant_prices');
    }
};
