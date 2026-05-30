<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    public function products()
    {
        return $this->belongsToMany(Product::class, 'offer_items', 'offer_id', 'item_id')
                    ->wherePivot('item_type', 'product');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'offer_items', 'offer_id', 'item_id')
                    ->wherePivot('item_type', 'category');
    }

    protected $fillable = [
        'name',
        'description',
        'type',
        'value',
        'buy_quantity',
        'get_quantity',
        'min_amount',
        'discount_percentage',
        'discount_amount',
        'applies_to',
        'start_date',
        'end_date',
        'is_active',
        'priority',
        'max_uses',
        'used_count',
        'is_stackable',
        'tier_thresholds',
        'flash_quantity',
    ];

    protected $casts = [
        'value'               => 'decimal:2',
        'min_amount'          => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount'     => 'decimal:2',
        'start_date'          => 'date',
        'end_date'            => 'date',
        'is_active'           => 'boolean',
        'is_stackable'        => 'boolean',
        'tier_thresholds'     => 'array',
    ];

    public static array $typeLabels = [
        'percentage'              => 'خصم نسبة مئوية',
        'fixed'                   => 'خصم مبلغ ثابت',
        'buy_x_get_y'             => 'اشتري X واحصل على Y مجاناً',
        'buy_x_get_discount'      => 'اشتري X واحصل على خصم',
        'buy_amount_get_discount' => 'أنفق مبلغاً واحصل على خصم',
        'product_price_discount'  => 'خصم على سعر المنتج',
        'tiered'                  => 'خصم متدرج',
        'flash'                   => 'عرض سريع (فلاش)',
        'free_shipping'           => 'شحن مجاني',
        'bundle'                  => 'حزمة منتجات',
        'first_order'             => 'خصم أول طلب',
    ];

    public static array $typeBadges = [
        'percentage'              => 'badge-light-info',
        'fixed'                   => 'badge-light-warning',
        'buy_x_get_y'             => 'badge-light-success',
        'buy_x_get_discount'      => 'badge-light-primary',
        'buy_amount_get_discount' => 'badge-light-dark',
        'product_price_discount'  => 'badge-light-danger',
        'tiered'                  => 'badge-light-warning',
        'flash'                   => 'badge-light-danger',
        'free_shipping'           => 'badge-light-success',
        'bundle'                  => 'badge-light-primary',
        'first_order'             => 'badge-light-info',
    ];

    public static array $appliesToLabels = [
        'product'  => 'منتج',
        'category' => 'فئة',
        'order'    => 'طلب',
    ];

    public static array $appliesToBadges = [
        'product'  => 'badge-light-primary',
        'category' => 'badge-light-warning',
        'order'    => 'badge-light-success',
    ];

    public function getTypeLabelAttribute(): string
    {
        return self::$typeLabels[$this->type] ?? $this->type;
    }

    public function getTypeBadgeAttribute(): string
    {
        return self::$typeBadges[$this->type] ?? 'badge-light-secondary';
    }

    public function getAppliesToLabelAttribute(): string
    {
        return self::$appliesToLabels[$this->applies_to] ?? $this->applies_to;
    }

    public function getAppliesToBadgeAttribute(): string
    {
        return self::$appliesToBadges[$this->applies_to] ?? 'badge-light-secondary';
    }

    public function isExpired(): bool
    {
        return $this->end_date < now()->toDateString();
    }

    public function isMaxedOut(): bool
    {
        return $this->max_uses !== null && $this->used_count >= $this->max_uses;
    }
}
