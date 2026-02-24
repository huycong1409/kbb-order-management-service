<?php

namespace App\Services;

use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository
    ) {}

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->orderRepository->paginate($filters, $perPage);
    }

    public function find(int $id)
    {
        return $this->orderRepository->find($id);
    }

    public function getForExport(array $filters = []): Collection
    {
        return $this->orderRepository->getForExport($filters);
    }

    public function getProfitByDate(int $shopId, string $date): float
    {
        return $this->orderRepository->getProfitByDate($shopId, $date);
    }

    public function getProfitByMonth(int $shopId, int $year, int $month): float
    {
        return $this->orderRepository->getProfitByMonth($shopId, $year, $month);
    }
}
