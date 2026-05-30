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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->string('description_ar')->nullable();
            $table->string('description_en')->nullable();
            $table->string('email')->nullable();
            $table->string('email2')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone2')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('address')->nullable();
            $table->string('address2')->nullable();
            $table->text('location')->nullable();
            $table->string('lat')->nullable();
            $table->string('lng')->nullable();
            $table->text('pdf')->nullable();
            $table->string('facebook')->nullable();
            $table->string('twitter')->nullable();
            $table->string('youtube')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('instagram')->nullable();
            $table->string('snapchat')->nullable();
            $table->string('tiktok')->nullable();
            $table->string('meta_keywords_ar')->nullable();
            $table->string('meta_keywords_en')->nullable();
            $table->string('meta_description_ar')->nullable();
            $table->string('meta_description_en')->nullable();
            $table->tinyInteger('num')->default('10');
            $table->text('appkey')->nullable();
            $table->text('authkey')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
