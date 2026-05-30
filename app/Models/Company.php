<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;

class Company extends Model implements HasMedia
{
    use  InteractsWithMedia, HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function registerMediaCollections(Media $media = null): void
    {
        $this->addMediaCollection('image')
        ->singleFile();

        $this->addMediaConversion('thumb')
        ->keepOriginalImageFormat()
        ->crop('crop-center', 150, 150);
    }

    public function users()
    {
        return $this->hasMany(CompanyUser::class);
    }
}
