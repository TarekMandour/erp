<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;

class CompanyUser extends Authenticatable implements HasMedia
{
    use Notifiable, InteractsWithMedia, HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function registerMediaCollections(Media $media = null): void
    {
        $this->addMediaCollection('image')
        ->singleFile();

        $this->addMediaConversion('thumb')
        ->keepOriginalImageFormat()
        ->crop('crop-center', 150, 150);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->is_active)) {
                $model->is_active = 1;
            }
        });
    }
}
