<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'report_date',
        'ads_raw_input',
        'ads_fee',
        'ads_refund',
    ];

    protected $casts = [
        'report_date' => 'date',
        'ads_fee'     => 'decimal:2',
        'ads_refund'  => 'decimal:2',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Chi phí ADS = ADS - Hoàn ADS
     */
    public function getAdsCostAttribute(): float
    {
        return max(0, (float) $this->ads_fee - (float) $this->ads_refund);
    }

    /**
     * Parse chuỗi ADS dạng "₫324.431" → 324431 * 1.08
     * Shopee dùng dấu chấm (.) làm phân cách ngàn và phẩy (,) cho thập phân.
     */
    public static function parseAdsInput(string $raw): float
    {
        // Bỏ ký hiệu tiền tệ và khoảng trắng
        $cleaned = preg_replace('/[₫\s]/u', '', $raw);
        // Thay dấu chấm ngàn → không có gì; giữ dấu phẩy thập phân → dấu chấm
        $cleaned = str_replace('.', '', $cleaned);
        $cleaned = str_replace(',', '.', $cleaned);
        $value   = (float) $cleaned;

        return round($value * 1.08, 2);
    }

    public function scopeForShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }
}
