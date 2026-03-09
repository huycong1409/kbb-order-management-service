<?php

namespace App\Http\Controllers;

use App\Exports\OrdersExport;
use App\Http\Requests\Order\ImportOrderRequest;
use App\Models\DailyReport;
use App\Services\OrderImportService;
use App\Services\OrderService;
use App\Services\ShopService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService       $orderService,
        private readonly OrderImportService $importService,
        private readonly ShopService        $shopService,
    ) {}

    /**
     * Danh sách đơn hàng - có filter theo shop, sản phẩm, ngày.
     */
    public function index(Request $request): View
    {
        $filters = $request->only('shop_id', 'search', 'product_id', 'product_name', 'date_from', 'date_to', 'status', 'loss_only', 'sort', 'dir');
        $perPage = (int) $request->input('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50, 100, 200]) ? $perPage : 20;
        $orders  = $this->orderService->list($filters, $perPage);
        $shops   = $this->shopService->allActive();

        // Tính lợi nhuận trước ADS và chi phí ADS theo từng ngày
        $rawDailyStats = $this->orderService->getDailyStats($filters);

        // Lấy chi phí ADS từ daily_reports theo ngày + shop tương ứng
        $allDates   = array_keys($rawDailyStats);
        $allShopIds = collect($rawDailyStats)->flatMap(fn ($d) => $d['shop_ids'])->unique()->values()->toArray();

        $adsMap = [];
        if (!empty($allDates) && !empty($allShopIds)) {
            DailyReport::whereIn('report_date', $allDates)
                ->whereIn('shop_id', $allShopIds)
                ->get()
                ->each(function ($r) use (&$adsMap) {
                    $day = $r->report_date->format('Y-m-d');
                    $adsMap[$day] = ($adsMap[$day] ?? 0) + $r->ads_cost;
                });
        }

        $dailyStats = [];
        foreach ($rawDailyStats as $day => $data) {
            $profitBeforeAds    = $data['profit'];
            $adsCost            = $adsMap[$day] ?? 0;
            $dailyStats[$day]   = [
                'profit_before_ads' => $profitBeforeAds,
                'ads_cost'          => $adsCost,
                'profit'            => $profitBeforeAds - $adsCost,
            ];
        }

        return view('orders.index', compact('orders', 'shops', 'filters', 'perPage', 'dailyStats'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters  = $request->only('shop_id', 'search', 'product_id', 'product_name', 'date_from', 'date_to', 'loss_only');
        $orders   = $this->orderService->getForExport($filters);
        $filename = 'don-hang-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new OrdersExport($orders), $filename);
    }

    public function show(int $id): View
    {
        $order = $this->orderService->find($id);
        return view('orders.show', compact('order'));
    }

    public function preview(int $id): \Illuminate\Http\JsonResponse
    {
        $order = $this->orderService->find($id);

        return response()->json([
            'order_code'   => $order->order_code,
            'order_date'   => $order->order_date->format('d/m/Y H:i'),
            'shop'         => $order->shop->name ?? '—',
            'status'       => $order->status,
            'buyer'        => $order->buyer_username,
            'recipient'    => $order->recipient_name,
            'province'     => $order->province,
            'fixed_fee'    => $order->fixed_fee,
            'service_fee'  => $order->service_fee,
            'payment_fee'  => $order->payment_fee,
            'pi_ship'      => $order->pi_ship,
            'total_selling'=> $order->total_selling_price,
            'total_tax'    => $order->total_tax,
            'total_cost'   => $order->total_cost,
            'profit'       => $order->profit,
            'items'        => $order->items->map(fn ($i) => [
                'product_name' => $i->product_name,
                'variant_name' => $i->variant_name,
                'quantity'     => $i->quantity,
                'cost_price'   => $i->effective_cost_price,
                'selling_price'=> $i->selling_price,
                'tax'          => round($i->tax, 0),
            ]),
        ]);
    }

    /**
     * Form import Excel.
     */
    public function importForm(): View
    {
        $shops = $this->shopService->allActive();
        return view('orders.import', compact('shops'));
    }

    /**
     * Xử lý import file Excel Shopee.
     */
    public function import(ImportOrderRequest $request): RedirectResponse
    {
        $stats = $this->importService->import(
            $request->file('file'),
            (int) $request->input('shop_id')
        );

        // Nếu phát hiện cột bị thay đổi → chặn import, yêu cầu kiểm tra file
        if (!empty($stats['column_warnings'])) {
            return redirect()->route('orders.import-form')
                ->with('warning', 'File có thể không đúng định dạng Shopee hoặc Shopee đã thay đổi cấu trúc cột. Dữ liệu CHƯA được import.')
                ->with('import_errors', $stats['column_warnings']);
        }

        $message = "Import hoàn tất: {$stats['imported']} đơn";

        if (!empty($stats['deleted'])) {
            $message .= " ({$stats['deleted']} đơn cũ đã được xoá và thay thế)";
        }
        if ($stats['skipped'] > 0) {
            $message .= ", {$stats['skipped']} bỏ qua (đơn hủy)";
        }

        if (!empty($stats['errors'])) {
            $message .= ', ' . count($stats['errors']) . ' lỗi';
            return redirect()->route('orders.import-form')
                ->with('warning', $message)
                ->with('import_errors', $stats['errors']);
        }

        return redirect()->route('orders.index')->with('success', $message);
    }
}
