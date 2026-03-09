<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_name',
        'product_sku',
        'variant_sku',
        'quantity',
        'cost_price',
        'original_price',
        'sale_price',
        'selling_price',
    ];

    protected $casts = [
        'quantity'       => 'integer',
        'cost_price'     => 'decimal:2',
        'original_price' => 'decimal:2',
        'sale_price'     => 'decimal:2',
        'selling_price'  => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Cache tĩnh để tra cứu giá vốn theo tên trong cùng 1 request.
     * Key: "product_name|variant_name|order_date", Value: float|null.
     */
    private static array $nameBasedCostCache = [];

    /**
     * Giá vốn hiệu lực — 4 bước fallback:
     *
     * 1. productVariant relationship (nếu cost > 0)
     * 2. product relationship (nếu cost > 0)
     * 3. Tra cứu theo tên + ngày đặt hàng:
     *    a. Tìm trong product_variant_histories theo order_date (phiên bản có hiệu lực lúc đặt)
     *    b. Tìm trong product_variants hiện tại (fallback khi chưa có history)
     *    c. Tìm trong product_histories theo order_date (giá vốn product-level)
     *    d. Tìm trong products hiện tại
     * 4. Fallback về cost_price đã lưu trong DB
     */
    public function getEffectiveCostPriceAttribute(): float
    {
        // 1. productVariant relationship có cost > 0
        if ($this->relationLoaded('productVariant') && $this->productVariant) {
            $cost = (float) $this->productVariant->cost_price;
            if ($cost > 0) return $cost;
        }

        // 2. product relationship có cost > 0
        if ($this->relationLoaded('product') && $this->product) {
            $cost = (float) $this->product->cost_price;
            if ($cost > 0) return $cost;
        }

        // 3. Tra cứu theo tên — có xét lịch sử phiên bản nếu biết order_date
        $productName = trim((string) ($this->product_name ?? ''));
        $variantName = trim((string) ($this->variant_name ?? ''));
        $orderDate   = $this->relationLoaded('order') ? $this->order?->order_date : null;

        if (!empty($productName)) {
            $cacheKey = $productName . '|' . $variantName . '|' . ($orderDate?->toDateString() ?? 'nodate');

            if (!array_key_exists($cacheKey, self::$nameBasedCostCache)) {
                $cost = null;

                if (!empty($variantName)) {
                    // 3a. Tìm trong product_variant_histories theo ngày đặt hàng
                    if ($orderDate) {
                        $found = ProductVariantHistory::whereHas('productHistory', function ($q) use ($productName, $orderDate) {
                            $q->where('name', $productName)
                              ->where('effective_from', '<=', $orderDate)
                              ->where(fn ($q2) => $q2->whereNull('effective_to')
                                  ->orWhere('effective_to', '>', $orderDate));
                        })
                        ->where('name', $variantName)
                        ->orderBy('cost_price', 'desc')
                        ->value('cost_price');

                        if ($found !== null && (float) $found > 0) {
                            $cost = (float) $found;
                        }
                    }

                    // 3b. Fallback: product_variants hiện tại theo tên
                    if ($cost === null) {
                        $found = ProductVariant::whereHas('product', fn ($q) => $q->where('name', $productName))
                            ->where('name', $variantName)
                            ->orderBy('cost_price', 'desc')
                            ->value('cost_price');

                        if ($found !== null && (float) $found > 0) {
                            $cost = (float) $found;
                        }
                    }
                }

                // 3c. Tìm trong product_histories (product-level) theo ngày
                if ($cost === null && $orderDate) {
                    $found = ProductHistory::where('name', $productName)
                        ->where('effective_from', '<=', $orderDate)
                        ->where(fn ($q) => $q->whereNull('effective_to')
                            ->orWhere('effective_to', '>', $orderDate))
                        ->orderBy('cost_price', 'desc')
                        ->value('cost_price');

                    if ($found !== null && (float) $found > 0) {
                        $cost = (float) $found;
                    }
                }

                // 3d. Fallback: products hiện tại theo tên
                if ($cost === null) {
                    $found = Product::where('name', $productName)
                        ->orderBy('cost_price', 'desc')
                        ->value('cost_price');

                    if ($found !== null && (float) $found > 0) {
                        $cost = (float) $found;
                    }
                }

                self::$nameBasedCostCache[$cacheKey] = $cost;
            }

            if (self::$nameBasedCostCache[$cacheKey] !== null) {
                return self::$nameBasedCostCache[$cacheKey];
            }
        }

        // 4. Fallback về giá trị đã lưu (kể cả 0)
        return (float) $this->cost_price;
    }

    /** Thuế = Tổng giá bán * 1.5% */
    public function getTaxAttribute(): float
    {
        return round((float) $this->selling_price * 0.015, 2);
    }

    /** Tổng vốn = Số lượng * Giá vốn hiệu lực */
    public function getTotalCostAttribute(): float
    {
        return round((float) $this->quantity * $this->effective_cost_price, 2);
    }

    /** Lợi nhuận thuần của item (chưa trừ phí chung của đơn hàng). */
    public function getItemProfitBeforeSharedFeesAttribute(): float
    {
        return (float) $this->selling_price - $this->tax - $this->total_cost;
    }
}
