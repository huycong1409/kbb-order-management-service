<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Services\ProductService;
use App\Services\ShopService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly ShopService    $shopService,
    ) {}

    public function all(Request $request): View
    {
        $filters  = $request->only('shop_id', 'search', 'is_active');
        $products = $this->productService->listAll($filters, 20);
        $shops    = $this->shopService->allActive();

        return view('products.all', compact('products', 'shops', 'filters'));
    }

    public function index(Request $request, int $shopId): View
    {
        $shop     = $this->shopService->find($shopId);
        $products = $this->productService->listForShop($shopId, $request->only('search', 'is_active'));
        return view('products.index', compact('shop', 'products'));
    }

    public function create(int $shopId): View
    {
        $shop = $this->shopService->find($shopId);
        return view('products.create', compact('shop'));
    }

    public function store(StoreProductRequest $request, int $shopId): RedirectResponse
    {
        $back = $request->input('_back');
        $this->productService->create($shopId, $request->validated());
        if ($back === 'products.all') {
            return redirect()->route('products.all', ['shop_id' => $shopId])
                ->with('success', 'Thêm sản phẩm thành công.');
        }
        return redirect()->route('shops.products.index', $shopId)
            ->with('success', 'Thêm sản phẩm thành công.');
    }

    public function edit(int $shopId, int $id): View
    {
        $shop    = $this->shopService->find($shopId);
        $product = $this->productService->find($id);
        return view('products.edit', compact('shop', 'product'));
    }

    public function update(UpdateProductRequest $request, int $shopId, int $id): RedirectResponse
    {
        $back = $request->input('_back');
        $this->productService->update($id, $request->validated());
        if ($back === 'products.all') {
            return redirect()->route('products.all', ['shop_id' => $shopId])
                ->with('success', 'Cập nhật sản phẩm thành công.');
        }
        return redirect()->route('shops.products.index', $shopId)
            ->with('success', 'Cập nhật sản phẩm thành công.');
    }

    public function destroy(Request $request, int $shopId, int $id): RedirectResponse
    {
        $back = $request->input('_back');
        $this->productService->delete($id);
        if ($back === 'products.all') {
            return redirect()->route('products.all')
                ->with('success', 'Xoá sản phẩm thành công.');
        }
        return redirect()->route('shops.products.index', $shopId)
            ->with('success', 'Xoá sản phẩm thành công.');
    }

    public function destroyVariant(int $shopId, int $productId, int $variantId): RedirectResponse
    {
        $this->productService->deleteVariant($variantId);
        return redirect()->route('shops.products.edit', [$shopId, $productId])
            ->with('success', 'Xoá phân loại thành công.');
    }

    /**
     * Xoá version hiện tại → restore về version mới nhất trong lịch sử.
     * Nếu không có lịch sử thì không cho xoá.
     */
    public function destroyCurrentVersion(Request $request, int $shopId, int $id): RedirectResponse
    {
        $back = $request->input('_back', '');
        $this->productService->rollbackToLatestHistory($id);
        $url = route('shops.products.edit', [$shopId, $id]) . ($back ? '?_back=' . $back : '');
        return redirect($url)->with('success', 'Đã xoá version hiện tại, đã khôi phục về version trước.');
    }

    /** Trang lịch sử phiên bản của 1 sản phẩm. */
    public function histories(int $id): View
    {
        $product = $this->productService->find($id);
        return view('products.histories', compact('product'));
    }

    /** Xoá 1 phiên bản lịch sử cụ thể, redirect về trang edit để xem phiên bản mới nhất. */
    public function destroyHistory(Request $request, int $id, int $historyId): RedirectResponse
    {
        $this->productService->deleteHistory($id, $historyId);
        $product = $this->productService->find($id);
        $back    = $request->input('_back', '');
        $url     = route('shops.products.edit', [$product->shop_id, $id]) . ($back ? '?_back=' . $back : '');
        return redirect($url)->with('success', 'Đã xoá phiên bản lịch sử.');
    }

    /**
     * API kéo thả thứ tự: nhận JSON {ids: [...], page: N, per_page: N}.
     * Trả về JSON {ok: true}.
     */
    public function reorder(Request $request): JsonResponse
    {
        $ids     = $request->input('ids', []);
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = max(1, (int) $request->input('per_page', 20));
        $offset  = ($page - 1) * $perPage;

        $positions = [];
        foreach (array_values($ids) as $index => $id) {
            $positions[(int) $id] = $offset + $index + 1;
        }

        $this->productService->reorder($positions);

        return response()->json(['ok' => true]);
    }
}
