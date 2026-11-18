<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\App;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;

class Admin extends Authenticatable implements HasMedia
{
    use Notifiable, InteractsWithMedia, HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * QA 1.5: لا تُكشف كلمة المرور أو رمز التذكر في أي رد JSON
     */
    protected $hidden = ['password', 'remember_token'];

    public function registerMediaCollections(Media $media = null): void
    {
        $this->addMediaCollection('image')
        ->singleFile();

        $this->addMediaConversion('thumb')
        ->keepOriginalImageFormat()
        ->crop('crop-center', 150, 150);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            // القيمة '0' (غير مفعل) صالحة؛ الافتراضي مفعل فقط إذا لم تُرسل الحالة
            if ($model->is_active === null || $model->is_active === '') {
                $model->is_active = 1;
            }
        });
    }
}
