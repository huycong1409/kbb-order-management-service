<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class OrdersExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    private array $rows;

    public function __construct(Collection $orders)
    {
        $this->rows = $this->flatten($orders);
    }

    private function flatten(Collection $orders): array
    {
        $rows = [];

        foreach ($orders as $order) {
            $items = $order->items;
            foreach ($items as $idx => $item) {
                $isFirst = ($idx === 0);
                $rows[] = [
                    $isFirst ? $order->order_code : '',
                    $isFirst ? $order->order_date->format('d/m/Y H:i') : '',
                    $isFirst ? ($order->shop->name ?? '') : '',
                    $item->product_name,
                    $item->variant_name ?? '',
                    $item->quantity,
                    $item->cost_price,
                    $item->selling_price,
                    $isFirst ? (float) $order->fixed_fee : '',
                    $isFirst ? (float) $order->service_fee : '',
                    $isFirst ? (float) $order->payment_fee : '',
                    $isFirst ? (float) $order->pi_ship : '',
                    round($item->tax, 0),
                    round($item->total_cost, 0),
                    $isFirst ? round($order->profit, 0) : '',
                ];
            }
        }

        return $rows;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Mã đơn hàng',
            'Ngày đặt',
            'Shop',
            'Tên sản phẩm',
            'Phân loại',
            'SL',
            'Giá vốn',
            'Tổng giá bán',
            'Phí cố định',
            'Phí dịch vụ',
            'Phí thanh toán',
            'Pi Ship',
            'Thuế (1.5%)',
            'Tổng vốn',
            'Lợi nhuận',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1E293B']],
            ],
        ];
    }
}
