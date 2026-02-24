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
    public function getProfitByDate(int $shopId, string $date): float;
    public function getProfitByMonth(int $shopId, int $year, int $month): float;
    public function getForExport(array $filters = []): Collection;
}
