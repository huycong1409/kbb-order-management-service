<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    public function allForShop(int $shopId): Collection
    {
        return Product::with('variants')
            ->forShop($shopId)
            ->orderBy('name')
            ->get();
    }

    public function paginateForShop(int $shopId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Product::with('variants')
            ->forShop($shopId)
            ->orderBy('name');

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('sku', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): Product
    {
        return Product::with('variants')->findOrFail($id);
    }

    public function findByNameAndShop(string $name, int $shopId): ?Product
    {
        return Product::with('variants')
            ->forShop($shopId)
            ->where('name', $name)
            ->first();
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->fresh('variants');
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
