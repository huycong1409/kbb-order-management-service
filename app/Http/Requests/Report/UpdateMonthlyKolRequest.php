<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMonthlyKolRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'shop_id'  => 'required|integer|exists:shops,id',
            'year'     => 'required|integer|min:2020|max:2099',
            'month'    => 'required|integer|min:1|max:12',
            'kol_cost' => 'required|numeric|min:0',
        ];
    }
}
