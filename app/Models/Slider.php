<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;

class Slider extends Model implements HasMedia
{
    use InteractsWithMedia ;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function registerMediaCollections(Media $media = null): void
    {
        $this->addMediaCollection('image')
        ->singleFile();
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->sort)) {
                $model->sort = 0;
            }
        });
    }

}
