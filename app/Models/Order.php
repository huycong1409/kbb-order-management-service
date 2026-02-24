<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'order_code',
        'package_code',
        'order_date',
        'status',
        'fixed_fee',
        'service_fee',
        'payment_fee',
        'pi_ship',
        'tracking_number',
        'shipping_carrier',
        'payment_method',
        'buyer_username',
        'recipient_name',
        'phone',
        'province',
        'address',
        'note',
    ];

    protected $casts = [
        'order_date'  => 'datetime',
        'fixed_fee'   => 'decimal:2',
        'service_fee' => 'decimal:2',
        'payment_fee' => 'decimal:2',
        'pi_ship'     => 'decimal:2',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Tổng phí cố định của đơn hàng (tính 1 lần).
     */
    public function getTotalSharedFeesAttribute(): float
    {
        return (float) ($this->fixed_fee + $this->service_fee + $this->payment_fee + $this->pi_ship);
    }

    /**
     * Tổng giá bán của tất cả items trong đơn.
     */
    public function getTotalSellingPriceAttribute(): float
    {
        return (float) $this->items->sum('selling_price');
    }

    /**
     * Tổng thuế của đơn hàng (1.5% mỗi dòng sản phẩm).
     */
    public function getTotalTaxAttribute(): float
    {
        return (float) $this->items->sum(fn ($item) => $item->tax);
    }

    /**
     * Tổng vốn của đơn hàng.
     */
    public function getTotalCostAttribute(): float
    {
        return (float) $this->items->sum(fn ($item) => $item->total_cost);
    }

    /**
     * Lợi nhuận = Tổng giá bán - (Phí cố định + Phí DV + Phí TT + Pi Ship + Thuế + Tổng vốn).
     */
    public function getProfitAttribute(): float
    {
        return $this->total_selling_price
            - $this->total_shared_fees
            - $this->total_tax
            - $this->total_cost;
    }

    public function scopeForShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInDateRange($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->whereDate('order_date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('order_date', '<=', $to);
        }
        return $query;
    }
}
