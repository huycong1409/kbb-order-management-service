<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariantHistory extends Model
{
    protected $fillable = [
        'product_history_id',
        'product_variant_id',
        'name',
        'sku',
        'cost_price',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
    ];

    public function productHistory(): BelongsTo
    {
        return $this->belongsTo(ProductHistory::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
