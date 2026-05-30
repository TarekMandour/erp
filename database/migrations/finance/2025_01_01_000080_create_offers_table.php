<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['percentage', 'fixed', 'buy_x_get_y', 'buy_x_get_discount', 'buy_amount_get_discount', 'product_price_discount']);
            $table->decimal('value', 15, 2)->nullable()->default(null);
            $table->integer('buy_quantity')->nullable();
            $table->integer('get_quantity')->nullable();
            $table->decimal('min_amount', 15, 2)->nullable();
            $table->decimal('discount_percentage', 10, 2)->nullable();
            $table->decimal('discount_amount', 15, 2)->nullable();
            $table->enum('applies_to', ['product', 'category', 'order']);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
