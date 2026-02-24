@extends('layouts.app')
@section('title', 'Sửa Sản phẩm')
@section('breadcrumb', $shop->name . ' / Sản phẩm / Chỉnh sửa')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('shops.products.index', $shop->id) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-bold">Sửa: {{ Str::limit($product->name, 60) }}</h5>
</div>

<form action="{{ route('shops.products.update', [$shop->id, $product->id]) }}" method="POST">
    @csrf @method('PUT')
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
                        <a href="{{ route('shops.products.index', $shop->id) }}" class="btn btn-outline-secondary">
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
