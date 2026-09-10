<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'sku',
        'barcode',
        'name',
        'description',
        'category_id',
        'brand_id',
        'unit_id',
        'sort',
        'purchase_price',
        'selling_price',
        'tax_rate',
        'alert_quantity',
        'is_active',
        'has_expiry',
        'has_variants',
        'average_cost',
        'cost_price',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'has_expiry' => 'boolean',
        'is_active' => 'boolean',
        'has_variants' => 'boolean',
        'deleted_at' => 'datetime'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function defaultVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'default_variant_id');
    }

    public function lots()
    {
        return $this->hasMany(InventoryLot::class);
    }

    public function mediass()
    {
        return $this->MorphManyPHP(Media::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function unitConversions()
    {
        return $this->hasMany(UnitConversion::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereHas('lots', function ($q) {
            $q->whereRaw('(quantity - reserved_quantity) <= products.alert_quantity')
                ->where('status', 'active');
        });
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('has_expiry', true)
            ->whereHas('lots', function ($q) use ($days) {
                $q->whereBetween('expiry_date', [now(), now()->addDays($days)])
                    ->where('status', 'active')
                    ->whereRaw('(quantity - reserved_quantity) > 0');
            });
    }

    public function getTotalQuantityAttribute()
    {
        return $this->lots()->sum('quantity');
    }

    public function getAvailableQuantityAttribute()
    {
        return $this->lots()->sum(\DB::raw('quantity - reserved_quantity'));
    }

    public function getTotalValueAttribute()
    {
        return $this->lots()->sum(\DB::raw('quantity * unit_cost'));
    }
    
    public function registerMediaCollections(Media $media = null): void
    {
        $this->addMediaCollection('thumbnail')
            ->singleFile();

        $this->addMediaCollection('gallery');
    }
}
