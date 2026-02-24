@extends('layouts.app')
@section('title', 'Thêm Sản phẩm')
@section('breadcrumb', $shop->name . ' / Sản phẩm / Thêm mới')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('shops.products.index', $shop->id) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-bold">Thêm Sản phẩm mới</h5>
    <span class="badge bg-light text-dark border">{{ $shop->name }}</span>
</div>

<form action="{{ route('shops.products.store', $shop->id) }}" method="POST">
    @csrf
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header fw-semibold">Thông tin cơ bản</div>
                <div class="card-body">
                    @include('products._form_basic')
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
                    <table class="table mb-0" id="variantsTable">
                        <thead>
                            <tr>
                                <th>Tên phân loại (VD: 20cm, 6 Chiếc)</th>
                                <th>SKU</th>
                                <th class="text-end">Giá vốn (₫)</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="variantRows">
                            @if(old('variants'))
                                @foreach(old('variants') as $i => $v)
                                    @include('products._variant_row', ['index' => $i, 'variant' => $v])
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                    <div id="noVariants" class="text-center text-muted py-3 {{ old('variants') ? 'd-none' : '' }}"
                         style="font-size:0.85rem">
                        <i class="bi bi-tags"></i> Nhấn "+ Thêm phân loại" để thêm phân loại hàng
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Lưu Sản phẩm
                        </button>
                        <a href="{{ route('shops.products.index', $shop->id) }}" class="btn btn-outline-secondary">
                            Huỷ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
let variantIndex = {{ old('variants') ? count(old('variants')) : 0 }};

function updateNoVariantsMsg() {
    const rows = document.querySelectorAll('#variantRows tr');
    document.getElementById('noVariants').classList.toggle('d-none', rows.length > 0);
}

document.getElementById('btnAddVariant').addEventListener('click', function () {
    const row = `
    <tr>
        <td><input type="text" name="variants[${variantIndex}][name]" class="form-control form-control-sm"
                   placeholder="VD: 20cm" required></td>
        <td><input type="text" name="variants[${variantIndex}][sku]" class="form-control form-control-sm"
                   placeholder="SKU phân loại"></td>
        <td><input type="number" name="variants[${variantIndex}][cost_price]" class="form-control form-control-sm text-end"
                   placeholder="0" min="0" required></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-variant">
            <i class="bi bi-x"></i></button></td>
    </tr>`;
    document.getElementById('variantRows').insertAdjacentHTML('beforeend', row);
    variantIndex++;
    updateNoVariantsMsg();
});

document.getElementById('variantRows').addEventListener('click', function (e) {
    if (e.target.closest('.btn-remove-variant')) {
        e.target.closest('tr').remove();
        updateNoVariantsMsg();
    }
});

updateNoVariantsMsg();
</script>
@endpush
