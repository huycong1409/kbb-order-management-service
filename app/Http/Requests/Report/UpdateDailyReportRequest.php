<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDailyReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'shop_id'       => 'required|integer|exists:shops,id',
            'date'          => 'required|date_format:Y-m-d',
            'ads_raw_input' => 'nullable|string|max:50',  // ₫324.431
            'ads_fee'       => 'nullable|numeric|min:0',  // nếu nhập trực tiếp số
            'ads_refund'    => 'nullable|numeric|min:0',  // Hoàn ADS
        ];
    }
}
