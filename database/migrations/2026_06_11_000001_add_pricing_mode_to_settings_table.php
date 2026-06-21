<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('pricing_mode')->default('exclusive')->after('authkey')
                  ->comment('exclusive = السعر لا يشمل الضريبة | inclusive = السعر شامل الضريبة');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('pricing_mode');
        });
    }
};
