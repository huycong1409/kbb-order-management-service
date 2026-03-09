<?php

namespace App\Http\Controllers;

use App\Http\Requests\Report\UpdateDailyReportRequest;
use App\Http\Requests\Report\UpdateMonthlyKolRequest;
use App\Services\ProductService;
use App\Services\ReportService;
use App\Services\ShopService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService  $reportService,
        private readonly ShopService    $shopService,
        private readonly ProductService $productService,
    ) {}

    /**
     * Báo cáo hiệu suất sản phẩm: filter theo shop, khoảng ngày, danh sách sản phẩm.
     */
    public function monthly(Request $request): View
    {
        $shops      = $this->shopService->allActive();
        $shopId     = (int) $request->input('shop_id', $shops->first()?->id ?? 0);
        $dateFrom   = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo     = $request->input('date_to', now()->format('Y-m-d'));
        $productIds = array_values(array_filter((array) $request->input('product_ids', [])));

        $report   = $this->reportService->getProductReport($shopId, $dateFrom, $dateTo, $productIds);
        $products = $shopId ? $this->productService->allForShop($shopId) : collect();

        return view('reports.monthly', compact(
            'shops', 'shopId', 'dateFrom', 'dateTo', 'productIds', 'report', 'products'
        ));
    }

    /**
     * Trang nhập chi phí ADS/KOL theo tháng.
     */
    public function adsEntry(Request $request): View
    {
        $shops  = $this->shopService->allActive();
        $shopId = (int) $request->input('shop_id', $shops->first()?->id ?? 0);
        $year   = (int) $request->input('year', now()->year);
        $month  = (int) $request->input('month', now()->month);

        $report    = $this->reportService->getMonthlyReport($shopId, $year, $month);
        $currStats = $this->reportService->getMonthStats($shopId, $year, $month);

        $prevYear  = $month === 1 ? $year - 1 : $year;
        $prevMonth = $month === 1 ? 12 : $month - 1;
        $prevStats = $this->reportService->getMonthStats($shopId, $prevYear, $prevMonth);

        return view('reports.ads', compact(
            'report', 'shops', 'shopId', 'year', 'month',
            'currStats', 'prevStats', 'prevMonth', 'prevYear'
        ));
    }

    /**
     * Trang so sánh chỉ số động: truyền danh sách shop + sản phẩm theo shop xuống JS.
     * Mỗi "item" so sánh được thêm/xoá động ở client, gọi AJAX compareStats để lấy số liệu.
     */
    public function compare(): View
    {
        $shops = $this->shopService->allActive();

        // Pre-load toàn bộ sản phẩm nhóm theo shop_id → inject vào JS
        $productsByShop = [];
        foreach ($shops as $shop) {
            $productsByShop[$shop->id] = $this->productService->allForShop($shop->id)
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])
                ->values()
                ->toArray();
        }

        return view('reports.compare', compact('shops', 'productsByShop'));
    }

    /**
     * AJAX: trả về thống kê cho 1 bộ lọc (shop + khoảng ngày + sản phẩm).
     */
    public function compareStats(Request $request): JsonResponse
    {
        $shopId     = (int) $request->input('shop_id', 0);
        $dateFrom   = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo     = $request->input('date_to', now()->format('Y-m-d'));
        $productIds = array_values(array_filter((array) $request->input('product_ids', [])));

        if (!$shopId) {
            return response()->json(['error' => 'shop_id bắt buộc'], 422);
        }

        $report = $this->reportService->getProductReport($shopId, $dateFrom, $dateTo, $productIds);

        return response()->json([
            'total_revenue' => $report['total_revenue'],
            'total_cost'    => $report['total_cost'],
            'total_profit'  => $report['total_profit'],
            'total_qty'     => $report['total_qty'],
            'products'      => $report['products']->values(),
        ]);
    }

    /**
     * Cập nhật ADS ngày (AJAX hoặc form submit).
     */
    public function updateDailyAds(UpdateDailyReportRequest $request): JsonResponse|RedirectResponse
    {
        $data   = $request->validated();
        $report = $this->reportService->updateDailyAds(
            (int) $data['shop_id'],
            $data['date'],
            $data
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success'    => true,
                'ads_fee'    => $report->ads_fee,
                'ads_refund' => $report->ads_refund,
                'ads_cost'   => $report->ads_cost,
            ]);
        }

        return back()->with('success', 'Cập nhật ADS thành công.');
    }

    /**
     * Cập nhật chi phí KOL theo tháng.
     */
    public function updateMonthlyKol(UpdateMonthlyKolRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $this->reportService->updateMonthlyKolCost(
            (int) $data['shop_id'],
            (int) $data['year'],
            (int) $data['month'],
            (float) $data['kol_cost']
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Cập nhật chi phí KOL thành công.');
    }
}
