<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_tree_id')->constrained('account_trees');
            $table->decimal('debit', 15, 2)->default(0.00);
            $table->decimal('credit', 15, 2)->default(0.00);
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete()->comment('مركز تكلفة (اختياري)');
            $table->text('description')->nullable()->comment('وصف تفصيلي للطرف');
            $table->timestamps();

            $table->index('journal_entry_id');
            $table->index('account_tree_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_items');
    }
};
