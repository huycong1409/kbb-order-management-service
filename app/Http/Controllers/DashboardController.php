<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ShopService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ShopService $shopService,
    ) {}

    public function index(Request $request): View
    {
        $shops  = $this->shopService->allActive();
        $shopId = (int) $request->input('shop_id', 0);  // 0 = tất cả
        $year   = (int) $request->input('year', now()->year);
        $month  = (int) $request->input('month', now()->month);

        // ── Stat cards tháng hiện tại ─────────────────────────────────────
        $ordersQuery = Order::with('items')
            ->whereYear('order_date', $year)
            ->whereMonth('order_date', $month);

        if ($shopId > 0) {
            $ordersQuery->where('shop_id', $shopId);
        }

        $monthOrders   = $ordersQuery->get();
        $totalOrders   = $monthOrders->count();
        $totalSelling  = $monthOrders->flatMap->items->sum('selling_price');
        $totalProfit   = $monthOrders->sum(fn ($o) => $o->profit);
        $lossOrders    = $monthOrders->filter(fn ($o) => $o->profit < 0)->count();

        // ── Doanh thu theo ngày (30 ngày gần nhất trong tháng) ────────────
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $chartLabels = [];
        $chartData   = [];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $dayOrders = $monthOrders->filter(
                fn ($o) => $o->order_date->format('Y-m-d') === $date
            );
            $chartLabels[] = $d . '/' . $month;
            $chartData[]   = round($dayOrders->sum(fn ($o) => $o->profit), 0);
        }

        // ── Top 10 sản phẩm bán chạy trong tháng ────────────────────────
        $topProductsQuery = OrderItem::query()
            ->select('product_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(selling_price) as total_selling'))
            ->whereHas('order', function ($q) use ($year, $month, $shopId) {
                $q->whereYear('order_date', $year)->whereMonth('order_date', $month);
                if ($shopId > 0) {
                    $q->where('shop_id', $shopId);
                }
            })
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        // ── So sánh tháng trước ───────────────────────────────────────────
        $prevMonth = $month === 1 ? 12 : $month - 1;
        $prevYear  = $month === 1 ? $year - 1 : $year;
        $prevQuery = Order::with('items')
            ->whereYear('order_date', $prevYear)
            ->whereMonth('order_date', $prevMonth);
        if ($shopId > 0) {
            $prevQuery->where('shop_id', $shopId);
        }
        $prevOrders = $prevQuery->get();
        $prevProfit = $prevOrders->sum(fn ($o) => $o->profit);
        $prevSelling = $prevOrders->flatMap->items->sum('selling_price');

        return view('dashboard.index', compact(
            'shops', 'shopId', 'year', 'month',
            'totalOrders', 'totalSelling', 'totalProfit', 'lossOrders',
            'chartLabels', 'chartData',
            'topProductsQuery',
            'prevProfit', 'prevSelling',
        ));
    }
}
