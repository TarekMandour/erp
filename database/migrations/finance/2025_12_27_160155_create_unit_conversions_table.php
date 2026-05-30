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
        Schema::create('unit_conversions', function (Blueprint $table) {
            $table->id();
            
            // المنتج أو النوع
            $table->foreignId('product_id')->nullable()->constrained(); 
            $table->foreignId('variant_id')->nullable()->constrained('product_variants');
            
            // الوحدات
            $table->string('base_unit', 50)->comment('الوحدة الأساسية مثل "piece"');
            $table->string('target_unit', 50)->comment('الوحدة الهدف مثل "box"');
            $table->decimal('conversion_rate', 15, 6)->comment('معامل التحويل');
            
            // معلومات إضافية
            $table->boolean('is_default')->default(false);
            $table->boolean('allow_fractions')->default(true)->comment('السماح بالكسور');
            $table->integer('decimal_places')->default(2)->comment('عدد المنازل للكسور');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Constraints
            $table->unique(['product_id', 'variant_id', 'base_unit', 'target_unit'], 'unique_conversion');
            
            // Indexes
            $table->index(['product_id', 'variant_id']);
            $table->index('base_unit');
            $table->index('target_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_conversions');
    }
};
