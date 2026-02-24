<?php

namespace App\Services;

use App\Models\Shop;
use App\Repositories\Contracts\ShopRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ShopService
{
    public function __construct(
        private readonly ShopRepositoryInterface $shopRepository
    ) {}

    public function list(): LengthAwarePaginator
    {
        return $this->shopRepository->paginate(15);
    }

    public function allActive(): Collection
    {
        return $this->shopRepository->allActive();
    }

    public function find(int $id): Shop
    {
        return $this->shopRepository->find($id);
    }

    public function create(array $data): Shop
    {
        return $this->shopRepository->create($data);
    }

    public function update(int $id, array $data): Shop
    {
        $shop = $this->shopRepository->find($id);
        return $this->shopRepository->update($shop, $data);
    }

    public function delete(int $id): void
    {
        $shop = $this->shopRepository->find($id);
        $this->shopRepository->delete($shop);
    }
}
