<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shop\StoreShopRequest;
use App\Http\Requests\Shop\UpdateShopRequest;
use App\Services\ShopService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function __construct(
        private readonly ShopService $shopService
    ) {}

    public function index(): View
    {
        $shops = $this->shopService->list();
        return view('shops.index', compact('shops'));
    }

    public function create(): View
    {
        return view('shops.create');
    }

    public function store(StoreShopRequest $request): RedirectResponse
    {
        $this->shopService->create($request->validated());
        return redirect()->route('shops.index')->with('success', 'Tạo shop thành công.');
    }

    public function edit(int $id): View
    {
        $shop = $this->shopService->find($id);
        return view('shops.edit', compact('shop'));
    }

    public function update(UpdateShopRequest $request, int $id): RedirectResponse
    {
        $this->shopService->update($id, $request->validated());
        return redirect()->route('shops.index')->with('success', 'Cập nhật shop thành công.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->shopService->delete($id);
        return redirect()->route('shops.index')->with('success', 'Xoá shop thành công.');
    }
}
