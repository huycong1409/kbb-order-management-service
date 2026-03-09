<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductHistory;
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

    public function listAll(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->productRepository->paginateAll($perPage, $filters);
    }

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

            // Sản phẩm mới luôn đứng ở cuối danh sách (sort_order = id)
            $product->update(['sort_order' => $product->id]);

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

            // Tự động tạo snapshot lịch sử khi có thay đổi field quan trọng
            if ($this->hasVersionedChanges($product, $data, $variants)) {
                $this->createHistorySnapshot($product);
            }

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

    /**
     * Lưu thứ tự kéo thả: nhận mảng [id => sort_order] và batch update.
     */
    public function reorder(array $positions): void
    {
        $this->productRepository->reorder($positions);
    }

    /**
     * Xoá 1 phiên bản lịch sử cụ thể.
     */
    public function deleteHistory(int $productId, int $historyId): void
    {
        ProductHistory::where('id', $historyId)
            ->where('product_id', $productId)
            ->firstOrFail()
            ->delete();
    }

    /**
     * Xoá version hiện tại → restore về version mới nhất trong lịch sử.
     * Lấy dữ liệu từ history entry có version cao nhất, apply lên product/variants,
     * rồi xoá history entry đó (nó trở thành trạng thái hiện tại).
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException nếu không có lịch sử
     */
    public function rollbackToLatestHistory(int $id): void
    {
        DB::transaction(function () use ($id) {
            $product = $this->productRepository->find($id);
            $history = $product->histories()->orderBy('version', 'desc')->firstOrFail();

            // Restore product-level fields
            $product->update([
                'name'        => $history->name,
                'sku'         => $history->sku,
                'cost_price'  => $history->cost_price,
                'is_active'   => $history->is_active,
                'description' => $history->description,
            ]);

            // Restore variant cost_price/name từ snapshot
            foreach ($history->variantHistories as $vh) {
                if ($vh->product_variant_id) {
                    ProductVariant::where('id', $vh->product_variant_id)->update([
                        'name'       => $vh->name,
                        'cost_price' => $vh->cost_price,
                    ]);
                }
            }

            // Xoá history entry này — nó đã trở thành trạng thái hiện tại
            $history->delete();
        });
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Kiểm tra xem có field quan trọng nào thay đổi không.
     * Chỉ tạo snapshot khi: tên SP, giá vốn SP, tên variant, hoặc giá vốn variant thay đổi.
     */
    private function hasVersionedChanges(Product $product, array $data, ?array $variants): bool
    {
        if (isset($data['name']) && $data['name'] !== $product->name) {
            return true;
        }

        if (isset($data['cost_price']) && (float) $data['cost_price'] !== (float) $product->cost_price) {
            return true;
        }

        if ($variants !== null) {
            foreach ($variants as $variantData) {
                if (empty($variantData['id'])) {
                    continue; // variant mới, không cần so sánh
                }
                $existing = $product->variants->firstWhere('id', $variantData['id']);
                if (!$existing) {
                    continue;
                }
                if (isset($variantData['name']) && $variantData['name'] !== $existing->name) {
                    return true;
                }
                if (isset($variantData['cost_price']) && (float) $variantData['cost_price'] !== (float) $existing->cost_price) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Tạo snapshot trạng thái hiện tại vào product_histories TRƯỚC khi apply thay đổi.
     * Mỗi snapshot đại diện cho 1 khoảng thời gian mà sản phẩm có tên/giá vốn đó.
     */
    private function createHistorySnapshot(Product $product): void
    {
        $latestHistory = $product->histories()->orderBy('version', 'desc')->first();
        $effectiveFrom = $latestHistory?->effective_to ?? $product->created_at;
        $nextVersion   = ($latestHistory?->version ?? 0) + 1;

        $history = ProductHistory::create([
            'product_id'     => $product->id,
            'version'        => $nextVersion,
            'name'           => $product->name,
            'sku'            => $product->sku,
            'cost_price'     => $product->cost_price,
            'is_active'      => $product->is_active,
            'description'    => $product->description,
            'effective_from' => $effectiveFrom,
            'effective_to'   => now(),
        ]);

        foreach ($product->variants as $variant) {
            $history->variantHistories()->create([
                'product_variant_id' => $variant->id,
                'name'               => $variant->name,
                'sku'                => $variant->sku,
                'cost_price'         => $variant->cost_price,
            ]);
        }
    }
}
