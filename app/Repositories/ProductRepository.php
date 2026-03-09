<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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

    public function paginateAll(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = Product::with(['variants', 'shop'])
            // sort_order = 0 → chưa sắp xếp, đẩy xuống cuối; > 0 → theo thứ tự đã set
            ->orderByRaw('CASE WHEN sort_order = 0 THEN 9999999 ELSE sort_order END')
            ->orderBy('shop_id')
            ->orderBy('name');

        if (!empty($filters['shop_id'])) {
            $query->where('shop_id', $filters['shop_id']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('sku', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): Product
    {
        return Product::with(['variants', 'histories.variantHistories'])->findOrFail($id);
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

    /**
     * Batch update sort_order: nhận mảng [product_id => sort_order].
     */
    public function reorder(array $positions): void
    {
        DB::transaction(function () use ($positions) {
            foreach ($positions as $id => $sortOrder) {
                Product::where('id', $id)->update(['sort_order' => $sortOrder]);
            }
        });
    }
}
