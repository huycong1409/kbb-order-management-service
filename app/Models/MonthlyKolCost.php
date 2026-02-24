<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyKolCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'year',
        'month',
        'kol_cost',
    ];

    protected $casts = [
        'year'     => 'integer',
        'month'    => 'integer',
        'kol_cost' => 'decimal:2',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function scopeForShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeForYearMonth($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }
}
