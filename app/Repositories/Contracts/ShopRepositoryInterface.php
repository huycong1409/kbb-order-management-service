<?php

namespace App\Repositories\Contracts;

use App\Models\Shop;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ShopRepositoryInterface
{
    public function all(): Collection;
    public function allActive(): Collection;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function find(int $id): Shop;
    public function create(array $data): Shop;
    public function update(Shop $shop, array $data): Shop;
    public function delete(Shop $shop): void;
}
