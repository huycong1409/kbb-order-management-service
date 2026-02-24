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
     * Cập nhật chi phí KOL theo tháng.
     */
    public function updateMonthlyKolCost(int $shopId, int $year, int $month, float $kolCost): void
    {
        $this->reportRepository->upsertMonthlyKolCost($shopId, $year, $month, $kolCost);
    }
}
