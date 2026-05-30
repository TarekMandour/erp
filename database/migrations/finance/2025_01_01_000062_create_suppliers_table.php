<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('commercial_number')->nullable();
            $table->string('bank')->nullable();
            $table->string('bank_account')->nullable();
            $table->decimal('wallet_balance', 15, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->enum('account_status', ['active', 'inactive', 'blocked'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
