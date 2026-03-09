<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface OrderRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;
    public function find(int $id): Order;
    public function findByCode(int $shopId, string $orderCode): ?Order;
    public function createOrUpdate(array $orderData, array $items): Order;
    public function deleteForShopInMonth(int $shopId, int $year, int $month): int;
    public function getProfitByDate(int $shopId, string $date): float;
    public function getProfitByMonth(int $shopId, int $year, int $month): float;
    public function getMonthSummaryStats(int $shopId, int $year, int $month): array;
    public function getForExport(array $filters = []): Collection;

    /**
     * Lấy thống kê doanh số/vốn/lợi nhuận theo từng sản phẩm, nhóm theo (product + shop).
     * Filters: shop_id, shop_ids[], date_from, date_to, product_ids[].
     */
    public function getProductStats(array $filters): Collection;

    /**
     * Lợi nhuận (trước ADS) mỗi ngày + shop_ids có mặt trong ngày đó.
     * Trả về: ['2026-03-09' => ['profit' => 1200000, 'shop_ids' => [1, 2]], ...]
     */
    public function getDailyStats(array $filters): array;
}
