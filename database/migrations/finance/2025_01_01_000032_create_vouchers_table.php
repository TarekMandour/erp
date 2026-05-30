<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['payment', 'receipt']);
            $table->enum('payment_type', ['cash', 'credit', 'installments']);
            $table->enum('party_type', ['customer', 'supplier', 'other']);
            $table->unsignedBigInteger('party_id')->nullable();
            $table->string('party_name')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->date('date');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
