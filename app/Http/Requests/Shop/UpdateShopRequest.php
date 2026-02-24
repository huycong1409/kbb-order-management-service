<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShopRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'        => 'sometimes|required|string|max:255',
            'platform'    => 'sometimes|required|string|in:shopee,lazada,tiki,sendo,other',
            'url'         => 'nullable|url|max:500',
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'boolean',
        ];
    }
}
