<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(
        private readonly ReportRepositoryInterface $reportRepository,
        private readonly OrderRepositoryInterface  $orderRepository,
    ) {}

    /**
     * Lấy báo cáo theo tháng cho 1 shop.
     * Trả về array theo ngày gồm:
     *   - profit_before_ads: lợi nhuận từ đơn hàng trong ngày
     *   - ads_fee, ads_refund, ads_cost (từ daily_reports)
     *   - daily_profit = profit_before_ads - ads_cost
     * Và tổng tháng:
     *   - monthly_profit = sum(daily_profit) - kol_cost
     */
    public function getMonthlyReport(int $shopId, int $year, int $month): array
    {
        $dailyReports = $this->reportRepository->getDailyReportsForMonth($shopId, $year, $month);
        $kolCost      = $this->reportRepository->getMonthlyKolCost($shopId, $year, $month);

        // Lấy lợi nhuận đơn hàng theo từng ngày
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $days        = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date          = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $dailyReport   = $dailyReports->firstWhere('report_date', $date);
            $profitBeforeAds = $this->orderRepository->getProfitByDate($shopId, $date);

            $adsFee    = $dailyReport?->ads_fee ?? 0;
            $adsRefund = $dailyReport?->ads_refund ?? 0;
            $adsCost   = max(0, $adsFee - $adsRefund);

            $days[$date] = [
                'date'              => $date,
                'profit_before_ads' => $profitBeforeAds,
                'ads_raw_input'     => $dailyReport?->ads_raw_input,
                'ads_fee'           => $adsFee,
                'ads_refund'        => $adsRefund,
                'ads_cost'          => $adsCost,
                'daily_profit'      => $profitBeforeAds - $adsCost,
            ];
        }

        $totalDailyProfit = collect($days)->sum('daily_profit');
        $kolCostValue     = (float) ($kolCost?->kol_cost ?? 0);

        return [
            'shop_id'        => $shopId,
            'year'           => $year,
            'month'          => $month,
            'days'           => array_values($days),
            'total_daily_profit' => $totalDailyProfit,
            'kol_cost'       => $kolCostValue,
            'monthly_profit' => $totalDailyProfit - $kolCostValue,
        ];
    }

    /**
     * Tóm tắt nhanh 1 tháng (dùng để so sánh với tháng trước).
     * Trả về: order_count, total_selling, profit_before_ads,
     *         total_ads_cost, daily_profit_total, kol_cost, monthly_profit.
     */
    public function getMonthStats(int $shopId, int $year, int $month): array
    {
        $orderStats   = $this->orderRepository->getMonthSummaryStats($shopId, $year, $month);
        $dailyReports = $this->reportRepository->getDailyReportsForMonth($shopId, $year, $month);
        $kolCost      = $this->reportRepository->getMonthlyKolCost($shopId, $year, $month);

        $totalAdsCost   = (float) $dailyReports->sum(fn ($r) => max(0, $r->ads_fee - $r->ads_refund));
        $kolCostValue   = (float) ($kolCost?->kol_cost ?? 0);
        $dailyProfitTotal = $orderStats['profit'] - $totalAdsCost;

        return [
            'order_count'        => $orderStats['order_count'],
            'total_selling'      => $orderStats['total_selling'],
            'total_cost'         => $orderStats['total_cost'],
            'profit_before_ads'  => $orderStats['profit'],
            'total_ads_cost'     => $totalAdsCost,
            'daily_profit_total' => $dailyProfitTotal,
            'kol_cost'           => $kolCostValue,
            'monthly_profit'     => $dailyProfitTotal - $kolCostValue,
        ];
    }

    /**
     * Cập nhật thông tin ADS cho 1 ngày.
     */
    public function updateDailyAds(int $shopId, string $date, array $data): DailyReport
    {
        $adsFee = 0;

        if (!empty($data['ads_raw_input'])) {
            $adsFee = DailyReport::parseAdsInput($data['ads_raw_input']);
        } elseif (isset($data['ads_fee'])) {
            $adsFee = (float) $data['ads_fee'];
        }

        return $this->reportRepository->upsertDailyReport($shopId, $date, [
            'ads_raw_input' => $data['ads_raw_input'] ?? null,
            'ads_fee'       => $adsFee,
            'ads_refund'    => (float) ($data['ads_refund'] ?? 0),
        ]);
    }

    /**
     * Báo cáo hiệu suất sản phẩm theo khoảng ngày + danh sách sản phẩm tùy chọn.
     * Trả về: total_revenue, total_cost, total_profit, total_qty, products (Collection).
     */
    public function getProductReport(int $shopId, string $dateFrom, string $dateTo, array $productIds = []): array
    {
        $stats = $this->orderRepository->getProductStats([
            'shop_id'     => $shopId,
            'date_from'   => $dateFrom,
            'date_to'     => $dateTo,
            'product_ids' => $productIds,
        ]);

        return [
            'total_revenue' => (float) $stats->sum('total_revenue'),
            'total_cost'    => (float) $stats->sum('total_cost'),
            'total_profit'  => (float) $stats->sum('total_profit'),
            'total_qty'     => (int) $stats->sum('total_qty'),
            'products'      => $stats,
        ];
    }

    /**
     * So sánh chỉ số sản phẩm giữa nhiều shop trong khoảng ngày.
     * Trả về mảng indexed theo product_id (hoặc hash tên):
     *   [ pid => ['product_name' => ..., 'shops' => [shop_id => stats]] ]
     * Sắp xếp giảm dần theo tổng doanh số.
     */
    public function getCompareData(array $shopIds, string $dateFrom, string $dateTo, array $productIds = []): array
    {
        if (empty($shopIds)) {
            return [];
        }

        $rows = $this->orderRepository->getProductStats([
            'shop_ids'    => $shopIds,
            'date_from'   => $dateFrom,
            'date_to'     => $dateTo,
            'product_ids' => $productIds,
        ]);

        $byProduct = [];
        foreach ($rows as $row) {
            $pid = $row->product_id ?? 'n_' . md5($row->product_name);
            if (!isset($byProduct[$pid])) {
                $byProduct[$pid] = [
                    'product_id'   => $row->product_id,
                    'product_name' => $row->product_name,
                    'shops'        => [],
                ];
            }
            $byProduct[$pid]['shops'][$row->shop_id] = [
                'total_qty'     => (int) $row->total_qty,
                'total_revenue' => (float) $row->total_revenue,
                'total_cost'    => (float) $row->total_cost,
                'total_profit'  => (float) $row->total_profit,
            ];
        }

        uasort($byProduct, function ($a, $b) {
            $sumA = array_sum(array_column($a['shops'], 'total_revenue'));
            $sumB = array_sum(array_column($b['shops'], 'total_revenue'));
            return $sumB <=> $sumA;
        });

        return $byProduct;
    }

    /**
     * Cập nhật chi phí KOL theo tháng.
     */
    public function updateMonthlyKolCost(int $shopId, int $year, int $month, float $kolCost): void
    {
        $this->reportRepository->upsertMonthlyKolCost($shopId, $year, $month, $kolCost);
    }
}
