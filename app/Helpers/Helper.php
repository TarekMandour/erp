<?php
namespace App\Helpers;

use App\Models\Setting;
use App\Models\Finance\Product;
use App\Models\Finance\ProductVariant;
use App\Models\Finance\ProductVariantPrice;

class Helper
{
    public static function settings()
    {
        $setting = Setting::find(1);
        return $setting;
    }

    /**
     * Returns the system pricing mode: 'inclusive' or 'exclusive'.
     * inclusive  = السعر المدخل للمنتج يشمل الضريبة
     * exclusive  = السعر المدخل لا يشمل الضريبة (الافتراضي)
     */
    public static function pricingMode(): string
    {
        return Setting::find(1)?->pricing_mode ?? 'exclusive';
    }

    /**
     * Returns the system default tax rate percentage (e.g. 15.00).
     */
    public static function defaultTaxRate(): float
    {
        return (float)(Setting::find(1)?->default_tax_rate ?? 15.00);
    }

    /**
     * Resolve the effective purchase/cost price for a product or variant.
     *
     * Priority:
     *   1. product_variants.average_cost  (if $variantId given and average_cost > 0)
     *   2. products.average_cost          (if > 0)
     *   3. products.purchase_price
     *
     * @param  int       $productId
     * @param  int|null  $variantId  Pass null for products without variants
     * @return float
     */
    public static function resolvePurchasePrice(int $productId, ?int $variantId = null): float
    {
        // ── 1. product_variants.average_cost ──────────────────────────
        if ($variantId) {
            $avgCost = ProductVariant::where('id', $variantId)->value('average_cost');
            if ($avgCost !== null && (float)$avgCost > 0) {
                return (float)$avgCost;
            }

            // ── 2. product_variants.purchase_price ─────────────────────
            $variantPrice = ProductVariant::where('id', $variantId)->value('purchase_price');
            if ($variantPrice !== null && (float)$variantPrice > 0) {
                return (float)$variantPrice;
            }
        }

        // ── 3. products.average_cost ──────────────────────────────────
        $productAvgCost = Product::where('id', $productId)->value('average_cost');
        if ($productAvgCost !== null && (float)$productAvgCost > 0) {
            return (float)$productAvgCost;
        }

        // ── 4. products.purchase_price ─────────────────────────────────
        $purchasePrice = Product::where('id', $productId)->value('purchase_price');
        return (float)($purchasePrice ?? 0);
    }

    /**
     * Resolve the effective selling price for a product or variant.
     *
     * Priority:
     *   1. product_variant_prices  (customer-specific first, then general)
     *   2. product_variants.selling_price  (if $variantId is given)
     *   3. products.selling_price
     *
     * @param  int       $productId
     * @param  int|null  $variantId   Pass null for products without variants
     * @param  int|null  $customerId  Pass null when no customer context
     * @return float
     */
    public static function resolveSellingPrice(int $productId, ?int $variantId = null, ?int $customerId = null): float
    {
        $today = now()->toDateString();

        // ── 1. product_variant_prices ──────────────────────────────────
        $pvpQuery = ProductVariantPrice::where('product_id', $productId)
            ->where('is_active', true)
            ->where(fn($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $today))
            ->where(fn($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', $today));

        if ($variantId) {
            $pvpQuery->where('variant_id', $variantId);
        } else {
            $pvpQuery->whereNull('variant_id');
        }

        // Customer-specific price
        if ($customerId) {
            $pvp = (clone $pvpQuery)
                ->where(fn($q) => $q
                    ->where('customer_id', $customerId)
                    ->orWhereJsonContains('customer_ids', $customerId)
                )
                ->orderByDesc('priority')
                ->first();

            if ($pvp) {
                return static::extractPvpPrice($pvp, $today);
            }
        }

        // General price (no customer restriction)
        $pvp = (clone $pvpQuery)
            ->whereNull('customer_id')
            ->where(fn($q) => $q->whereNull('customer_ids')->orWhere('customer_ids', '[]'))
            ->whereNull('customer_group_id')
            ->orderByDesc('priority')
            ->first();

        if ($pvp) {
            return static::extractPvpPrice($pvp, $today);
        }

        // ── 2. product_variants.selling_price (only when variant given) ─
        if ($variantId) {
            $variantPrice = ProductVariant::where('id', $variantId)->value('selling_price');
            if ($variantPrice !== null && (float)$variantPrice > 0) {
                return (float)$variantPrice;
            }
        }

        // ── 3. products.selling_price ──────────────────────────────────
        $productPrice = Product::where('id', $productId)->value('selling_price');
        return (float)($productPrice ?? 0);
    }

    /**
     * Extract the active price from a ProductVariantPrice record,
     * honouring sale_price window if set.
     */
    public static function extractPvpPrice(ProductVariantPrice $record, string $today): float
    {
        if ($record->sale_price !== null
            && (!$record->sale_start || $record->sale_start <= $today)
            && (!$record->sale_end   || $record->sale_end   >= $today)) {
            return (float)$record->sale_price;
        }
        return (float)$record->price;
    }
}