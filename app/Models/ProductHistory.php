<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductHistory extends Model
{
    protected $fillable = [
        'product_id',
        'version',
        'name',
        'sku',
        'cost_price',
        'is_active',
        'description',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'cost_price'     => 'decimal:2',
        'is_active'      => 'boolean',
        'effective_from' => 'datetime',
        'effective_to'   => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variantHistories(): HasMany
    {
        return $this->hasMany(ProductVariantHistory::class);
    }
}
