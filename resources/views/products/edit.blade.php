@extends('layouts.app')
@section('title', 'Sửa Sản phẩm')
@section('breadcrumb', $shop->name . ' / Sản phẩm / Chỉnh sửa')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ request('_back') === 'products.all' ? route('products.all', ['shop_id' => $shop->id]) : route('shops.products.index', $shop->id) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-bold">Sửa: {{ Str::limit($product->name, 60) }}</h5>
</div>

<form action="{{ route('shops.products.update', [$shop->id, $product->id]) }}" method="POST">
    @csrf @method('PUT')
    <input type="hidden" name="_back" value="{{ request('_back', '') }}">
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header fw-semibold">Thông tin cơ bản</div>
                <div class="card-body">
                    @include('products._form_basic', ['product' => $product])
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Phân loại hàng (Variants)</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddVariant">
                        <i class="bi bi-plus-lg"></i> Thêm phân loại
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Tên phân loại</th>
                                <th>SKU</th>
                                <th class="text-end">Giá vốn (₫)</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="variantRows">
                            @php $i = 0; @endphp
                            @foreach($product->variants as $variant)
                                @include('products._variant_row', [
                                    'index'     => $i++,
                                    'variant'   => ['id' => $variant->id, 'name' => $variant->name, 'sku' => $variant->sku, 'cost_price' => $variant->cost_price],
                                    'shopId'    => $shop->id,
                                    'productId' => $product->id,
                                ])
                            @endforeach
                            @if(old('variants'))
                                @foreach(old('variants') as $idx => $v)
                                    @if(empty($v['id']))
                                        @include('products._variant_row', ['index' => $i++, 'variant' => $v])
                                    @endif
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                    @if($product->variants->isEmpty())
                    <div class="text-center text-muted py-3" style="font-size:0.85rem">
                        <i class="bi bi-tags"></i> Nhấn "+ Thêm phân loại" để thêm phân loại hàng
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Lưu thay đổi
                        </button>
                        <a href="{{ request('_back') === 'products.all' ? route('products.all', ['shop_id' => $shop->id]) : route('shops.products.index', $shop->id) }}" class="btn btn-outline-secondary">
                            Huỷ
                        </a>
                    </div>
                    <hr>
                    <small class="text-muted">
                        Để xoá phân loại đã có, nhấn nút <i class="bi bi-x"></i> ở bên phải phân loại đó.
                    </small>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Lịch sử phiên bản --}}
@if($product->histories->isNotEmpty())
<div class="mt-4">
    <form action="{{ route('shops.products.current-version.destroy', [$shop->id, $product->id]) }}"
          method="POST"
          data-confirm="Xoá version hiện tại và khôi phục về version trước?"
          onsubmit="return confirm(this.dataset.confirm)">
        @csrf @method('DELETE')
        <input type="hidden" name="_back" value="{{ request('_back', '') }}">
        <button type="submit" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-arrow-counterclockwise"></i> Xoá version hiện tại
        </button>
    </form>
</div>
<div class="mt-4">
    <h6 class="text-muted mb-2">
        <i class="bi bi-clock-history me-1"></i> Lịch sử thay đổi
        <span class="badge bg-secondary ms-1">{{ $product->histories->count() }}</span>
    </h6>

    @foreach($product->histories as $history)
    <div class="card mb-2">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <div>
                <span class="badge bg-secondary me-1">v{{ $history->version }}</span>
                <span class="text-muted small">
                    {{ $history->effective_from->format('d/m/Y H:i') }}
                    →
                    {{ $history->effective_to?->format('d/m/Y H:i') ?? 'hiện tại' }}
                </span>
            </div>
            <form action="{{ route('products.histories.destroy', [$product->id, $history->id]) }}"
                  method="POST"
                  data-confirm="Xoá phiên bản v{{ $history->version }}?"
                  onsubmit="return confirm(this.dataset.confirm)">
                @csrf @method('DELETE')
                <input type="hidden" name="_back" value="{{ request('_back', '') }}">
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash"></i> Xoá version
                </button>
            </form>
        </div>
        <div class="card-body py-2">
            <div class="row g-2">
                <div class="col-md-5">
                    <div class="mb-1">
                        <span class="text-muted" style="font-size:0.75rem">Tên sản phẩm</span>
                        <div class="fw-semibold" style="font-size:0.85rem">{{ $history->name }}</div>
                    </div>
                    <div>
                        <span class="text-muted" style="font-size:0.75rem">Giá vốn mặc định</span>
                        <div class="num" style="font-size:0.85rem">{{ number_format($history->cost_price) }}₫</div>
                    </div>
                </div>
                <div class="col-md-7">
                    <span class="text-muted d-block" style="font-size:0.75rem">Phân loại & Giá vốn</span>
                    <div class="d-flex flex-wrap gap-1 mt-1">
                        @foreach($history->variantHistories as $vh)
                        <span class="badge bg-light text-dark border" style="font-size:0.7rem">
                            {{ $vh->name }}:
                            <span class="{{ $vh->cost_price > 0 ? 'text-success fw-semibold' : 'text-danger' }}">
                                {{ number_format($vh->cost_price) }}₫
                            </span>
                        </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection

@push('scripts')
<script>
let variantIndex = {{ $product->variants->count() }};

document.getElementById('btnAddVariant').addEventListener('click', function () {
    const row = `
    <tr>
        <td><input type="text" name="variants[${variantIndex}][name]" class="form-control form-control-sm"
                   placeholder="VD: 20cm" required></td>
        <td><input type="text" name="variants[${variantIndex}][sku]" class="form-control form-control-sm"></td>
        <td><input type="number" name="variants[${variantIndex}][cost_price]" class="form-control form-control-sm text-end"
                   placeholder="0" min="0" required></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-variant">
            <i class="bi bi-x"></i></button></td>
    </tr>`;
    document.getElementById('variantRows').insertAdjacentHTML('beforeend', row);
    variantIndex++;
});

document.getElementById('variantRows').addEventListener('click', function (e) {
    if (e.target.closest('.btn-remove-variant')) {
        e.target.closest('tr').remove();
    }
});
</script>
@endpush
