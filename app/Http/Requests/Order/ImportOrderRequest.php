<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class ImportOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'shop_id' => 'required|integer|exists:shops,id',
            'file'    => 'required|file|mimes:xlsx,xls|max:20480', // max 20MB
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'File import phải có định dạng .xlsx hoặc .xls',
            'file.max'   => 'File import không được vượt quá 20MB',
        ];
    }
}
