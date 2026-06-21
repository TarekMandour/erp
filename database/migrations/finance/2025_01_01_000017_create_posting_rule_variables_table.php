<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posting_rule_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('posting_rules')->cascadeOnDelete();
            $table->string('variable_name', 100)->comment('اسم المتغير (مثل total_tax, shipping_cost)');
            $table->enum('source_type', ['field', 'function', 'subquery']);
            $table->string('source_value', 255)->comment('مصدر القيمة');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posting_rule_variables');
    }
};
