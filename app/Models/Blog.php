<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function registerMediaCollections(Media $media = null): void
    {
        $this->addMediaCollection('photo');

        $this->addMediaConversion('thumb')
        ->keepOriginalImageFormat()
        ->width(600);

        $this->addMediaCollection('video');
    }
}
