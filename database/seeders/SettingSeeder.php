<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $setting = Setting::create([
            'name_ar' => 'نظام تجريبي',
            'name_en' => 'demo system',
            'email' => 'info@company.com',
            'phone' => '01006287379',
            'whatsapp' => '201006287379',
            'address' => 'عنوان تجريبي عنوان تجريبي',
            'facebook' => 'facebook link',
            'meta_keywords_ar' => 'كلمات دلاليه',
            'meta_description_ar' => 'وصف النظام',
        ]);

    }
}
