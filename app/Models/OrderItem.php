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
     * Thuế = Tổng giá bán * 1.5%
     */
    public function getTaxAttribute(): float
    {
        return round((float) $this->selling_price * 0.015, 2);
    }

    /**
     * Tổng vốn = Số lượng * Giá vốn
     */
    public function getTotalCostAttribute(): float
    {
        return round((float) $this->quantity * (float) $this->cost_price, 2);
    }

    /**
     * Lợi nhuận thuần của item (chưa trừ phí chung của đơn hàng).
     * Phí chung được tính tổng ở Order level.
     */
    public function getItemProfitBeforeSharedFeesAttribute(): float
    {
        return (float) $this->selling_price - $this->tax - $this->total_cost;
    }
}
