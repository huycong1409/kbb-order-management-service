<?php

namespace App\Repositories;

use App\Models\Shop;
use App\Repositories\Contracts\ShopRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ShopRepository implements ShopRepositoryInterface
{
    public function all(): Collection
    {
        return Shop::orderBy('name')->get();
    }

    public function allActive(): Collection
    {
        return Shop::active()->orderBy('name')->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Shop::orderBy('name')->paginate($perPage);
    }

    public function find(int $id): Shop
    {
        return Shop::findOrFail($id);
    }

    public function create(array $data): Shop
    {
        return Shop::create($data);
    }

    public function update(Shop $shop, array $data): Shop
    {
        $shop->update($data);
        return $shop->fresh();
    }

    public function delete(Shop $shop): void
    {
        $shop->delete();
    }
}
