@extends('layouts.app')
@section('title', 'Quản lý Sản phẩm')
@section('breadcrumb', 'Sản phẩm')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Quản lý Sản phẩm</h5>
        <small class="text-muted">{{ $products->total() }} sản phẩm</small>
    </div>
    @if(request('shop_id'))
        <a href="{{ route('shops.products.create', request('shop_id')) }}?_back=products.all"
           class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Thêm Sản phẩm
        </a>
    @else
        <div class="dropdown">
            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-plus-lg"></i> Thêm Sản phẩm
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach($shops as $shop)
                    <li>
                        <a class="dropdown-item" href="{{ route('shops.products.create', $shop->id) }}?_back=products.all">
                            <i class="bi bi-shop me-1 text-muted"></i> {{ $shop->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

{{-- Filter --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <select name="shop_id" class="form-select form-select-sm" style="width:180px" onchange="this.form.submit()">
                <option value="">Tất cả Shop</option>
                @foreach($shops as $shop)
                    <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>
                        {{ $shop->name }}
                    </option>
                @endforeach
            </select>
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
            @if(request()->hasAny(['shop_id', 'search', 'is_active']))
                <a href="{{ route('products.all') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x"></i>
                </a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    {{-- Drag-drop chỉ hiện khi không có search/filter để tránh nhầm lẫn thứ tự --}}
    @php $isDragEnabled = !request()->hasAny(['search', 'is_active']); @endphp
    @if($isDragEnabled)
    <div class="px-3 pt-2 pb-1">
        <small class="text-muted">
            <i class="bi bi-grip-vertical"></i> Kéo thả hàng để thay đổi thứ tự hiển thị.
            <span id="sortSaving" class="text-primary d-none ms-2"><i class="bi bi-arrow-repeat spin"></i> Đang lưu...</span>
            <span id="sortSaved" class="text-success d-none ms-2"><i class="bi bi-check-lg"></i> Đã lưu</span>
        </small>
    </div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    @if($isDragEnabled)<th style="width:32px"></th>@endif
                    <th>Shop</th>
                    <th>Tên Sản phẩm</th>
                    <th>SKU</th>
                    <th class="text-end">Giá vốn mặc định</th>
                    <th>Phân loại &amp; Giá vốn</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody id="productTableBody">
                @forelse($products as $product)
                <tr data-id="{{ $product->id }}">
                    @if($isDragEnabled)
                    <td class="drag-handle text-muted" style="cursor:grab; width:32px; vertical-align:middle">
                        <i class="bi bi-grip-vertical"></i>
                    </td>
                    @endif
                    <td>
                        <span class="badge bg-danger-subtle text-danger" style="font-size:.65rem">
                            {{ $product->shop->name ?? '—' }}
                        </span>
                    </td>
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
                                        {{ $v->name }}:
                                        @if($v->cost_price > 0)
                                            <span class="text-success fw-semibold">{{ number_format($v->cost_price) }}₫</span>
                                        @else
                                            <span class="text-danger">0₫</span>
                                        @endif
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
                            <a href="{{ route('shops.products.edit', [$product->shop_id, $product->id]) }}?_back=products.all"
                               class="btn btn-sm btn-outline-primary" title="Sửa">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('shops.products.destroy', [$product->shop_id, $product->id]) }}"
                                  method="POST"
                                  onsubmit="return confirm('Xoá sản phẩm \'{{ addslashes($product->name) }}\'?')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="_back" value="products.all">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Xoá">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $isDragEnabled ? 7 : 6 }}" class="text-center text-muted py-5">
                        <i class="bi bi-box-seam fs-2 d-block mb-2 opacity-25"></i>
                        Không có sản phẩm nào.
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

@push('scripts')
@if($isDragEnabled)
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
(function () {
    const tbody     = document.getElementById('productTableBody');
    const saving    = document.getElementById('sortSaving');
    const saved     = document.getElementById('sortSaved');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const page      = {{ $products->currentPage() }};
    const perPage   = {{ $products->perPage() }};
    let saveTimer   = null;

    Sortable.create(tbody, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'table-active',
        onEnd: function () {
            clearTimeout(saveTimer);
            saving.classList.remove('d-none');
            saved.classList.add('d-none');

            // Debounce 400ms để tránh save liên tục khi kéo nhanh
            saveTimer = setTimeout(async function () {
                const ids = Array.from(tbody.querySelectorAll('tr[data-id]'))
                    .map(tr => parseInt(tr.dataset.id));

                try {
                    const resp = await fetch('{{ route("products.reorder") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ ids, page, per_page: perPage }),
                    });

                    if (!resp.ok) throw new Error('HTTP ' + resp.status);

                    saving.classList.add('d-none');
                    saved.classList.remove('d-none');
                    setTimeout(() => saved.classList.add('d-none'), 2000);
                } catch (e) {
                    saving.classList.add('d-none');
                    alert('Lưu thứ tự thất bại. Vui lòng thử lại.');
                }
            }, 400);
        },
    });
})();
</script>
<style>
.spin { animation: spin .6s linear infinite; display:inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }
tr[data-id]:hover .drag-handle { color: #6c757d !important; }
</style>
@endif
@endpush
