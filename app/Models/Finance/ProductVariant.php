<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'attributes',
        'purchase_price',
        'selling_price',
        'cost_price',
        'average_cost',
        'alert_quantity',
        'sort_order',
        'is_active',
        'is_default',
        'weight',
        'width',
        'height',
        'length',
    ];

    protected $casts = [
        'attributes' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'average_cost' => 'decimal:2'
    ];
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function inventoryStocks()
    {
        return $this->hasMany(InventoryItem::class, 'product_id', 'id');
    }
    
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    
    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }
    
    public function unitConversions()
    {
        return $this->hasMany(UnitConversion::class);
    }

    public function getFormattedAttributesAttribute(): array
    {
        // ✅ Correct way (no conflict)
        $attrs = $this->getAttributeValue('attributes');

        if (is_string($attrs)) {
            $attrs = html_entity_decode($attrs, ENT_QUOTES, 'UTF-8');
            $attrs = json_decode($attrs, true);
        }

        if (!is_array($attrs) || empty($attrs)) {
            return [];
        }

        $names = Attribute::whereIn('id', array_keys($attrs))
            ->pluck('name', 'id')
            ->toArray();

        return collect($attrs)->mapWithKeys(fn ($value, $key) => [
            $names[$key] ?? $key => $value
        ])->toArray();
    }

    public function getFormattedAttributesTextAttribute(): string
    {
        return collect($this->formatted_attributes)
            ->map(fn ($value, $name) => "{$name}: {$value}")
            ->implode(' | ');
    }

    public function prices()
    {
        return $this->hasMany(ProductVariantPrice::class, 'variant_id');
    }

}
