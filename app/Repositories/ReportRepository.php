<?php

namespace App\Repositories;

use App\Models\DailyReport;
use App\Models\MonthlyKolCost;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ReportRepository implements ReportRepositoryInterface
{
    public function getDailyReportsForMonth(int $shopId, int $year, int $month): Collection
    {
        return DailyReport::forShop($shopId)
            ->whereYear('report_date', $year)
            ->whereMonth('report_date', $month)
            ->orderBy('report_date')
            ->get();
    }

    public function upsertDailyReport(int $shopId, string $date, array $data): DailyReport
    {
        return DailyReport::updateOrCreate(
            ['shop_id' => $shopId, 'report_date' => $date],
            $data
        );
    }

    public function getMonthlyKolCost(int $shopId, int $year, int $month): ?MonthlyKolCost
    {
        return MonthlyKolCost::forShop($shopId)
            ->forYearMonth($year, $month)
            ->first();
    }

    public function upsertMonthlyKolCost(int $shopId, int $year, int $month, float $kolCost): MonthlyKolCost
    {
        return MonthlyKolCost::updateOrCreate(
            ['shop_id' => $shopId, 'year' => $year, 'month' => $month],
            ['kol_cost' => $kolCost]
        );
    }
}
