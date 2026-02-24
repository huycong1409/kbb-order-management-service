<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'                    => 'required|string|max:500',
            'sku'                     => 'nullable|string|max:100',
            'cost_price'              => 'required|numeric|min:0',
            'description'             => 'nullable|string',
            'is_active'               => 'boolean',
            'variants'                => 'nullable|array',
            'variants.*.name'         => 'required|string|max:200',
            'variants.*.sku'          => 'nullable|string|max:100',
            'variants.*.cost_price'   => 'required|numeric|min:0',
        ];
    }
}
