<?php
namespace App\Helpers;

use App\Models\Setting;

class Helper
{
    public static function settings()
    {
        $setting = Setting::find(1);
        return $setting;
    }
}