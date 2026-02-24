<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    public function allForShop(int $shopId): Collection;
    public function paginateForShop(int $shopId, int $perPage = 15, array $filters = []): LengthAwarePaginator;
    public function find(int $id): Product;
    public function findByNameAndShop(string $name, int $shopId): ?Product;
    public function create(array $data): Product;
    public function update(Product $product, array $data): Product;
    public function delete(Product $product): void;
}
