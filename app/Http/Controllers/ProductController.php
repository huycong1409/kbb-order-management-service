<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Services\ProductService;
use App\Services\ShopService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly ShopService    $shopService,
    ) {}

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
        $this->productService->create($shopId, $request->validated());
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
        $this->productService->update($id, $request->validated());
        return redirect()->route('shops.products.index', $shopId)
            ->with('success', 'Cập nhật sản phẩm thành công.');
    }

    public function destroy(int $shopId, int $id): RedirectResponse
    {
        $this->productService->delete($id);
        return redirect()->route('shops.products.index', $shopId)
            ->with('success', 'Xoá sản phẩm thành công.');
    }

    public function destroyVariant(int $shopId, int $productId, int $variantId): RedirectResponse
    {
        $this->productService->deleteVariant($variantId);
        return redirect()->route('shops.products.edit', [$shopId, $productId])
            ->with('success', 'Xoá phân loại thành công.');
    }
}
