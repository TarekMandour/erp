<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Brand extends Model implements HasMedia    
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'sort',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deleted_at' => 'datetime'
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    public function scopeNotActive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%$search%")
                ->orWhere('code', 'like', "%{$search}%");
        });
    }

    public function registerMediaCollections(Media $media = null): void
    {
        $this->addMediaCollection('image')
        ->singleFile();
    }


}
