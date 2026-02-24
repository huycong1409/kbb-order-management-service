<?php

namespace App\Http\Controllers;

use App\Http\Requests\Report\UpdateDailyReportRequest;
use App\Http\Requests\Report\UpdateMonthlyKolRequest;
use App\Services\ReportService;
use App\Services\ShopService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly ShopService   $shopService,
    ) {}

    /**
     * Báo cáo theo tháng: hiển thị từng ngày với lợi nhuận, ADS, KOL.
     */
    public function monthly(Request $request): View
    {
        $shopId = (int) $request->input('shop_id', $this->shopService->allActive()->first()?->id ?? 0);
        $year   = (int) $request->input('year', now()->year);
        $month  = (int) $request->input('month', now()->month);

        $report = $this->reportService->getMonthlyReport($shopId, $year, $month);
        $shops  = $this->shopService->allActive();

        return view('reports.monthly', compact('report', 'shops', 'shopId', 'year', 'month'));
    }

    /**
     * Cập nhật ADS ngày (AJAX hoặc form submit).
     */
    public function updateDailyAds(UpdateDailyReportRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $report = $this->reportService->updateDailyAds(
            (int) $data['shop_id'],
            $data['date'],
            $data
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success'   => true,
                'ads_fee'   => $report->ads_fee,
                'ads_refund' => $report->ads_refund,
                'ads_cost'  => $report->ads_cost,
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
