@extends('layouts.app')
@section('title', 'Sản phẩm - ' . $shop->name)
@section('breadcrumb', $shop->name . ' / Sản phẩm')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('shops.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h5 class="mb-0 fw-bold">{{ $shop->name }}</h5>
                <small class="text-muted">{{ $products->total() }} sản phẩm</small>
            </div>
        </div>
    </div>
    <a href="{{ route('shops.products.create', $shop->id) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Thêm Sản phẩm
    </a>
</div>

{{-- Filter --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <input type="text" name="search" class="form-control form-control-sm" style="width:260px"
                   value="{{ request('search') }}" placeholder="Tìm tên sản phẩm, SKU...">
            <select name="is_active" class="form-select form-select-sm" style="width:150px">
                <option value="">Tất cả trạng thái</option>
                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Đang bán</option>
                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Ngừng bán</option>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-search"></i> Lọc
            </button>
            @if(request()->hasAny(['search', 'is_active']))
                <a href="{{ route('shops.products.index', $shop->id) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x"></i> Xoá bộ lọc
                </a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tên Sản phẩm</th>
                    <th>SKU</th>
                    <th class="text-end">Giá vốn mặc định</th>
                    <th>Phân loại</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td class="text-muted">{{ $product->id }}</td>
                    <td>
                        <div class="fw-semibold">{{ $product->name }}</div>
                        @if($product->description)
                            <small class="text-muted">{{ Str::limit($product->description, 60) }}</small>
                        @endif
                    </td>
                    <td><code class="text-muted">{{ $product->sku ?? '—' }}</code></td>
                    <td class="num fw-semibold text-end">{{ number_format($product->cost_price) }}₫</td>
                    <td>
                        @if($product->variants->isEmpty())
                            <span class="text-muted">—</span>
                        @else
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($product->variants->take(5) as $v)
                                    <span class="badge bg-light text-dark border" style="font-size:0.7rem">
                                        {{ $v->name }}: {{ number_format($v->cost_price) }}₫
                                    </span>
                                @endforeach
                                @if($product->variants->count() > 5)
                                    <span class="text-muted" style="font-size:0.75rem">+{{ $product->variants->count() - 5 }}</span>
                                @endif
                            </div>
                        @endif
                    </td>
                    <td>
                        @if($product->is_active)
                            <span class="badge bg-success-subtle text-success">Đang bán</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">Ngừng bán</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('shops.products.edit', [$shop->id, $product->id]) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i> Sửa
                            </a>
                            <form action="{{ route('shops.products.destroy', [$shop->id, $product->id]) }}"
                                  method="POST"
                                  onsubmit="return confirm('Xoá sản phẩm: {{ addslashes($product->name) }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <i class="bi bi-box-seam fs-2 d-block mb-2 opacity-25"></i>
                        Chưa có sản phẩm nào.
                        <a href="{{ route('shops.products.create', $shop->id) }}">Thêm sản phẩm đầu tiên</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($products->hasPages())
    <div class="card-footer bg-white d-flex justify-content-end">
        {{ $products->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
