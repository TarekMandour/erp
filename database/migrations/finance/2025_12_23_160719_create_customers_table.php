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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->unique();
            $table->string('name'); 
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->enum('account_status', ['active', 'inactive', 'blocked'])->default('active');
            $table->decimal('wallet_balance', 15, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->decimal('total_spent', 15, 2)->default(0);
            $table->date('join_date');
            $table->text('notes')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tax_number')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['phone']);
            $table->index(['account_status']);
            $table->index(['customer_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
