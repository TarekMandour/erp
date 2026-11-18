<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitConversion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'variant_id',
        'base_unit',
        'target_unit',
        'conversion_rate',
        'is_default',
        'allow_fractions',
        'decimal_places',
    ];

    protected $casts = [
        'conversion_rate' => 'decimal:4',
        'is_default' => 'boolean',
        'allow_fractions' => 'boolean',
        'decimal_places' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function targetUnit()
    {
        return $this->belongsTo(Unit::class, 'target_unit', 'id');
    }

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit', 'id');
    }

    /**
     * QA (قرار 3): وحدة الإدخال والنص المعروض.
     *
     * المعامل دائماً = عدد وحدات المخزون في وحدة الإدخال (الكمية × المعامل = كمية المخزون).
     * يوجد في البيانات اتجاهان لتسجيل نفس التحويل:
     *  - «علبة → حبة × 20» (الأساسية هي الكبرى) — كما أدخلها فريق الاختبار
     *  - «حبة → علبة × 20» (الأساسية هي وحدة المنتج) — كما افترض الكود الأصلي
     * في الحالتين وحدة الإدخال هي الطرف الذي ليس وحدة المخزون، والنص: «علبة (= 20 حبة)».
     * كان النص يعرض الوحدة المستهدفة دائماً فظهر معكوساً لبيانات الاتجاه الأول.
     */
    public function entryUnit(?int $stockUnitId): ?Unit
    {
        if ($stockUnitId && (int) $this->base_unit === (int) $stockUnitId) {
            return $this->targetUnit;
        }

        return $this->baseUnit;
    }

    public function stockUnit(?int $stockUnitId): ?Unit
    {
        if ($stockUnitId && (int) $this->base_unit === (int) $stockUnitId) {
            return $this->baseUnit;
        }

        return $this->targetUnit;
    }

    /** هل يربط التحويل وحدة إدخال بوحدة مخزون المنتج مباشرة؟ */
    public function touchesStockUnit(?int $stockUnitId): bool
    {
        return ! $stockUnitId
            || (int) $this->base_unit === (int) $stockUnitId
            || (int) $this->target_unit === (int) $stockUnitId;
    }

    public function labelFor(?int $stockUnitId): string
    {
        return ($this->entryUnit($stockUnitId)?->name ?? '—')
            . ' (= ' . rtrim(rtrim((string) $this->conversion_rate, '0'), '.') . ' '
            . ($this->stockUnit($stockUnitId)?->name ?? '—') . ')';
    }

    /**
     * خيارات وحدات البيع/الشراء لمنتج: فقط التحويلات المرتبطة مباشرة بوحدة المخزون،
     * لأن الكمية تُضرب في المعامل: «علبة → شريط × 2» كانت تخصم حبتين بدل 20.
     */
    public static function dropdownFor(int $productId, ?int $variantId = null): \Illuminate\Support\Collection
    {
        $stockUnit = Product::where('id', $productId)->value('unit_id');

        return static::with(['baseUnit', 'targetUnit'])
            ->where('product_id', $productId)
            ->when(
                $variantId,
                fn ($q) => $q->where(fn ($q2) => $q2->where('variant_id', $variantId)->orWhereNull('variant_id')),
                fn ($q) => $q->whereNull('variant_id')
            )
            ->get()
            ->filter(fn ($c) => $c->touchesStockUnit($stockUnit))
            ->map(fn ($c) => [
                'id'              => $c->id,
                'text'            => $c->labelFor($stockUnit),
                'base_unit'       => $c->base_unit,
                'target_unit'     => $c->target_unit,
                'conversion_rate' => (float) $c->conversion_rate,
                'is_default'      => (bool) $c->is_default,
                'allow_fractions' => (bool) $c->allow_fractions,
                'decimal_places'  => (int) $c->decimal_places,
            ])
            ->values();
    }
}
