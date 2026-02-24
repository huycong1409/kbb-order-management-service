<?php

namespace App\Repositories\Contracts;

use App\Models\DailyReport;
use App\Models\MonthlyKolCost;
use Illuminate\Database\Eloquent\Collection;

interface ReportRepositoryInterface
{
    public function getDailyReportsForMonth(int $shopId, int $year, int $month): Collection;
    public function upsertDailyReport(int $shopId, string $date, array $data): DailyReport;
    public function getMonthlyKolCost(int $shopId, int $year, int $month): ?MonthlyKolCost;
    public function upsertMonthlyKolCost(int $shopId, int $year, int $month, float $kolCost): MonthlyKolCost;
}
