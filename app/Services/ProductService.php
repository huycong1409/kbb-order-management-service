<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {}

    public function listForShop(int $shopId, array $filters = []): LengthAwarePaginator
    {
        return $this->productRepository->paginateForShop($shopId, 15, $filters);
    }

    public function allForShop(int $shopId): Collection
    {
        return $this->productRepository->allForShop($shopId);
    }

    public function find(int $id): Product
    {
        return $this->productRepository->find($id);
    }

    public function create(int $shopId, array $data): Product
    {
        return DB::transaction(function () use ($shopId, $data) {
            $variants = $data['variants'] ?? [];
            unset($data['variants']);

            $product = $this->productRepository->create(array_merge($data, ['shop_id' => $shopId]));

            foreach ($variants as $variant) {
                $product->variants()->create($variant);
            }

            return $product->load('variants');
        });
    }

    public function update(int $id, array $data): Product
    {
        return DB::transaction(function () use ($id, $data) {
            $product  = $this->productRepository->find($id);
            $variants = $data['variants'] ?? null;
            unset($data['variants']);

            $product = $this->productRepository->update($product, $data);

            if ($variants !== null) {
                foreach ($variants as $variantData) {
                    if (!empty($variantData['id'])) {
                        ProductVariant::findOrFail($variantData['id'])->update($variantData);
                    } else {
                        $product->variants()->create($variantData);
                    }
                }
            }

            return $product->load('variants');
        });
    }

    public function delete(int $id): void
    {
        $product = $this->productRepository->find($id);
        $this->productRepository->delete($product);
    }

    public function deleteVariant(int $variantId): void
    {
        ProductVariant::findOrFail($variantId)->delete();
    }
}
