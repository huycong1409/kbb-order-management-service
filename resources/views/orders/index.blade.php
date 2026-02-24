@extends('layouts.app')
@section('title', 'Danh sách Đơn hàng')
@section('breadcrumb', 'Đơn hàng')

@push('styles')
<style>
.orders-table { table-layout: auto; }
.orders-table th, .orders-table td { white-space: nowrap; font-size: 0.78rem; padding: .35rem .55rem; }
.order-first-row > td { background: #eff6ff !important; border-top: 2px solid #93c5fd !important; }
.order-sub-row > td { background: #f8fafc; }
.order-total-row > td { background: #f0fdf4; border-bottom: 2px solid #86efac; }
.order-total-row td { font-weight: 600; font-size: 0.75rem; }
.col-money { text-align: right; font-family: 'Courier New', monospace; }
.profit-positive { color: #059669; font-weight: 700; }
.profit-negative { color: #dc2626; font-weight: 700; }
.order-loss-first > td { background: #fff1f2 !important; border-top: 2px solid #fca5a5 !important; }
.order-loss-sub > td { background: #fff5f5 !important; }
/* Ép table không vỡ ra ngoài main-content */
.main-content { min-width: 0; overflow-x: hidden; }
.table-scroll-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.btn-copy {
    background: none; border: none; padding: 0 2px; color: #94a3b8;
    cursor: pointer; font-size: .75rem; line-height: 1; opacity: 0;
    transition: opacity .15s, color .15s;
}
tr:hover .btn-copy { opacity: 1; }
.btn-copy:hover { color: #0ea5e9; }
.btn-copy.copied { color: #059669; opacity: 1; }
.sort-link { color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 3px; }
.sort-link:hover { color: #0ea5e9; }
.sort-icon { font-size: .65rem; opacity: .4; }
.sort-icon.active { opacity: 1; color: #0ea5e9; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Danh sách Đơn hàng</h5>
        <small class="text-muted">{{ $orders->total() }} đơn hàng</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('orders.export', request()->query()) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-download"></i> Xuất Excel
        </a>
        <a href="{{ route('orders.import-form') }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel"></i> Import Excel
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label mb-1 fw-semibold" style="font-size:.75rem">Shop</label>
                <select name="shop_id" class="form-select form-select-sm" style="width:180px">
                    <option value="">Tất cả Shop</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>
                            {{ $shop->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label mb-1 fw-semibold" style="font-size:.75rem">Từ ngày</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="{{ request('date_from') }}" style="width:145px">
            </div>
            <div>
                <label class="form-label mb-1 fw-semibold" style="font-size:.75rem">Đến ngày</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="{{ request('date_to') }}" style="width:145px">
            </div>
            <div>
                <label class="form-label mb-1 fw-semibold" style="font-size:.75rem">Tìm kiếm</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       value="{{ request('search') }}" placeholder="Mã đơn, tên SP..." style="width:220px">
            </div>
            <div class="align-self-end">
                <div class="form-check form-switch mb-0" style="padding-top:.3rem">
                    <input class="form-check-input" type="checkbox" name="loss_only" id="lossOnly"
                           value="1" {{ request('loss_only') ? 'checked' : '' }} onchange="this.form.submit()">
                    <label class="form-check-label text-danger fw-semibold" for="lossOnly" style="font-size:.8rem">
                        Đơn lỗ
                    </label>
                </div>
            </div>
            <div class="d-flex gap-1 align-self-end">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-search"></i> Lọc
                </button>
                @if(request()->hasAny(['shop_id','date_from','date_to','search','product_id','loss_only']))
                    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Summary stats --}}
@php
    $totalSelling = $orders->getCollection()->flatMap->items->sum('selling_price');
    $totalProfit  = $orders->getCollection()->sum(fn($o) => $o->profit);
@endphp
<div class="row g-2 mb-3">
    <div class="col-auto">
        <div class="stat-card" style="background:#3b82f6">
            <div class="label">Tổng đơn (trang này)</div>
            <div class="value">{{ $orders->count() }}</div>
        </div>
    </div>
    <div class="col-auto">
        <div class="stat-card" style="background:#8b5cf6">
            <div class="label">Tổng giá bán</div>
            <div class="value">{{ number_format($totalSelling) }}₫</div>
        </div>
    </div>
    <div class="col-auto">
        <div class="stat-card" style="background:{{ $totalProfit >= 0 ? '#059669' : '#dc2626' }}">
            <div class="label">Lợi nhuận</div>
            <div class="value">{{ number_format($totalProfit) }}₫</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-scroll-wrapper">
        <table class="table orders-table mb-0">
            @php
                $curSort = request('sort', 'order_date');
                $curDir  = request('dir', 'desc');
                $sortUrl = function(string $col) use ($curSort, $curDir): string {
                    $dir = ($curSort === $col && $curDir === 'desc') ? 'asc' : 'desc';
                    return request()->fullUrlWithQuery(['sort' => $col, 'dir' => $dir, 'page' => 1]);
                };
                $sortIcon = function(string $col) use ($curSort, $curDir): string {
                    if ($curSort !== $col) return '<i class="bi bi-arrow-down-up sort-icon"></i>';
                    return $curDir === 'asc'
                        ? '<i class="bi bi-arrow-up sort-icon active"></i>'
                        : '<i class="bi bi-arrow-down sort-icon active"></i>';
                };
            @endphp
            <thead>
                <tr>
                    <th>Mã ĐH</th>
                    <th>
                        <a href="{{ $sortUrl('order_date') }}" class="sort-link">
                            Ngày đặt {!! $sortIcon('order_date') !!}
                        </a>
                    </th>
                    <th>Shop</th>
                    <th>Tên sản phẩm</th>
                    <th>Phân loại</th>
                    <th class="col-money">SL</th>
                    <th class="col-money">Giá vốn</th>
                    <th class="col-money">
                        <a href="{{ $sortUrl('total_selling') }}" class="sort-link">
                            Tổng giá bán {!! $sortIcon('total_selling') !!}
                        </a>
                    </th>
                    <th class="col-money">Phí cố định</th>
                    <th class="col-money">Phí DV</th>
                    <th class="col-money">Phí TT</th>
                    <th class="col-money">Pi Ship</th>
                    <th class="col-money">Thuế (1.5%)</th>
                    <th class="col-money">Tổng vốn</th>
                    <th class="col-money">
                        <a href="{{ $sortUrl('profit') }}" class="sort-link">
                            Lợi nhuận {!! $sortIcon('profit') !!}
                        </a>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    @php
                        $items        = $order->items;
                        $itemCount    = $items->count();
                        $orderProfit  = $order->profit;
                    @endphp

                    @foreach($items as $idx => $item)
                    @php
                        $isFirst = ($idx === 0);
                        $tax     = $item->tax;
                        $tVon    = $item->total_cost;
                    @endphp
                    @php
                        $isLoss = $orderProfit < 0;
                        $rowClass = $isFirst
                            ? ($isLoss ? 'order-loss-first' : 'order-first-row')
                            : ($isLoss ? 'order-loss-sub'   : 'order-sub-row');
                    @endphp
                    <tr class="{{ $rowClass }}">
                        {{-- Mã đơn: chỉ hiển thị ở dòng đầu --}}
                        <td>
                            @if($isFirst)
                                <div class="d-flex align-items-center gap-1">
                                    <a href="#" onclick="openPreview({{ $order->id }}, '{{ route('orders.show', $order->id) }}'); return false;"
                                       class="fw-semibold text-decoration-none text-primary"
                                       title="Xem nhanh">
                                        {{ $order->order_code }}
                                    </a>
                                    <button type="button" class="btn-copy" title="Copy mã đơn"
                                            onclick="copyCode('{{ $order->order_code }}', this)">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            @endif
                        </td>
                        <td>{{ $isFirst ? $order->order_date->format('d/m/Y H:i') : '' }}</td>
                        <td>
                            @if($isFirst)
                                <span class="badge bg-danger-subtle text-danger" style="font-size:.65rem">
                                    {{ $order->shop->name ?? '—' }}
                                </span>
                            @endif
                        </td>
                        <td style="max-width:280px; white-space:normal">{{ $item->product_name }}</td>
                        <td>{{ $item->variant_name ?? '—' }}</td>
                        <td class="col-money">{{ $item->quantity }}</td>
                        <td class="col-money">{{ number_format($item->cost_price) }}</td>
                        <td class="col-money">{{ number_format($item->selling_price) }}</td>

                        {{-- Phí: chỉ dòng đầu --}}
                        <td class="col-money">{{ $isFirst ? number_format($order->fixed_fee) : '' }}</td>
                        <td class="col-money">{{ $isFirst ? number_format($order->service_fee) : '' }}</td>
                        <td class="col-money">{{ $isFirst ? number_format($order->payment_fee) : '' }}</td>
                        <td class="col-money">{{ $isFirst ? number_format($order->pi_ship) : '' }}</td>

                        <td class="col-money">{{ number_format($tax) }}</td>
                        <td class="col-money">{{ number_format($tVon) }}</td>

                        {{-- Lợi nhuận: chỉ dòng đầu cho toàn đơn --}}
                        <td class="col-money">
                            @if($isFirst)
                                <span class="{{ $orderProfit >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                    {{ number_format($orderProfit) }}
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach

                    {{-- Total row nếu có nhiều item --}}
                    @if($itemCount > 1)
                    <tr class="order-total-row">
                        <td colspan="7" class="text-end text-muted">
                            Tổng {{ $itemCount }} sản phẩm:
                        </td>
                        <td class="col-money">{{ number_format($order->total_selling_price) }}</td>
                        <td class="col-money">{{ number_format($order->fixed_fee) }}</td>
                        <td class="col-money">{{ number_format($order->service_fee) }}</td>
                        <td class="col-money">{{ number_format($order->payment_fee) }}</td>
                        <td class="col-money">{{ number_format($order->pi_ship) }}</td>
                        <td class="col-money">{{ number_format($order->total_tax) }}</td>
                        <td class="col-money">{{ number_format($order->total_cost) }}</td>
                        <td class="col-money {{ $orderProfit >= 0 ? 'profit-positive' : 'profit-negative' }}">
                            {{ number_format($orderProfit) }}
                        </td>
                    </tr>
                    @endif
                @empty
                <tr>
                    <td colspan="15" class="text-center text-muted py-5">
                        <i class="bi bi-receipt fs-2 d-block mb-2 opacity-25"></i>
                        Không có đơn hàng nào.
                        <a href="{{ route('orders.import-form') }}">Import Excel để bắt đầu</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">
        @if($orders->hasPages())
        <div class="d-flex justify-content-center">
            {{ $orders->appends(request()->query())->links() }}
        </div>
        @endif
        <div class="d-flex justify-content-between align-items-center mt-2">
            <small class="text-muted">
                @if($orders->total() > 0)
                    Hiển thị {{ $orders->firstItem() }}–{{ $orders->lastItem() }} / {{ $orders->total() }} đơn
                @endif
            </small>
            <form method="GET" class="d-flex align-items-center gap-2">
                @foreach(request()->except('per_page', 'page') as $key => $val)
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                @endforeach
                <label class="text-muted mb-0" style="font-size:.75rem;white-space:nowrap">Hiển thị</label>
                <select name="per_page" class="form-select form-select-sm" style="width:80px" onchange="this.form.submit()">
                    @foreach([10, 20, 50, 100, 200] as $n)
                        <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
                <span class="text-muted" style="font-size:.75rem">/ trang</span>
            </form>
        </div>
    </div>
</div>
{{-- Modal xem nhanh đơn hàng --}}
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2" style="background:#1e293b">
                <h6 class="modal-title text-white mb-0">
                    <i class="bi bi-receipt me-1"></i>
                    <span id="pm-code">—</span>
                    <span class="badge bg-secondary ms-2" id="pm-shop" style="font-size:.65rem"></span>
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="pm-loading" class="text-center py-5">
                    <div class="spinner-border spinner-border-sm text-primary"></div>
                </div>
                <div id="pm-content" style="display:none">
                    <div class="px-3 pt-3 pb-2 d-flex flex-wrap gap-3" style="font-size:.8rem; background:#f8fafc; border-bottom:1px solid #e2e8f0">
                        <span><i class="bi bi-calendar3 text-muted me-1"></i><span id="pm-date"></span></span>
                        <span><i class="bi bi-person text-muted me-1"></i><span id="pm-buyer"></span></span>
                        <span><i class="bi bi-geo-alt text-muted me-1"></i><span id="pm-province"></span></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0" style="font-size:.78rem">
                            <thead>
                                <tr style="background:#f8fafc">
                                    <th>Sản phẩm</th><th>Phân loại</th>
                                    <th class="text-end">SL</th>
                                    <th class="text-end">Giá vốn</th>
                                    <th class="text-end">Giá bán</th>
                                    <th class="text-end">Thuế</th>
                                </tr>
                            </thead>
                            <tbody id="pm-items"></tbody>
                        </table>
                    </div>
                    <div class="px-3 py-2 d-flex flex-wrap gap-3 justify-content-end" style="font-size:.78rem; border-top:1px solid #e2e8f0; background:#f8fafc">
                        <span class="text-muted">Phí CĐ: <strong id="pm-fixed"></strong></span>
                        <span class="text-muted">Phí DV: <strong id="pm-service"></strong></span>
                        <span class="text-muted">Phí TT: <strong id="pm-payment"></strong></span>
                        <span class="text-muted">Pi Ship: <strong id="pm-piship"></strong></span>
                        <span class="text-muted">Tổng giá bán: <strong id="pm-selling"></strong></span>
                        <span class="text-muted">Tổng vốn: <strong id="pm-cost"></strong></span>
                        <span class="fw-bold" id="pm-profit-wrap">LN: <strong id="pm-profit"></strong></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <a id="pm-detail-link" href="#" class="btn btn-sm btn-primary">
                    <i class="bi bi-arrow-right-circle"></i> Xem chi tiết
                </a>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyCode(code, btn) {
    navigator.clipboard.writeText(code).then(() => {
        const icon = btn.querySelector('i');
        icon.className = 'bi bi-clipboard-check';
        btn.classList.add('copied');
        setTimeout(() => {
            icon.className = 'bi bi-clipboard';
            btn.classList.remove('copied');
        }, 1500);
    });
}

// ── Modal xem nhanh ──────────────────────────────────────────
const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));

function fmt(n) {
    if (n === '' || n === null || n === undefined) return '—';
    return Math.round(Number(n)).toLocaleString('vi-VN');
}

function openPreview(id, detailUrl) {
    document.getElementById('pm-loading').style.display = '';
    document.getElementById('pm-content').style.display  = 'none';
    document.getElementById('pm-detail-link').href = detailUrl;
    previewModal.show();

    fetch(`/orders/${id}/preview`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(d => {
            document.getElementById('pm-code').textContent     = d.order_code;
            document.getElementById('pm-shop').textContent     = d.shop;
            document.getElementById('pm-date').textContent     = d.order_date;
            document.getElementById('pm-buyer').textContent    = d.buyer || '—';
            document.getElementById('pm-province').textContent = d.province || '—';
            document.getElementById('pm-fixed').textContent    = fmt(d.fixed_fee);
            document.getElementById('pm-service').textContent  = fmt(d.service_fee);
            document.getElementById('pm-payment').textContent  = fmt(d.payment_fee);
            document.getElementById('pm-piship').textContent   = fmt(d.pi_ship);
            document.getElementById('pm-selling').textContent  = fmt(d.total_selling);
            document.getElementById('pm-cost').textContent     = fmt(d.total_cost);
            const profitEl = document.getElementById('pm-profit');
            profitEl.textContent = fmt(d.profit);
            profitEl.className = d.profit >= 0 ? 'profit-positive' : 'profit-negative';

            const tbody = document.getElementById('pm-items');
            tbody.innerHTML = d.items.map(i => `
                <tr>
                    <td>${i.product_name}</td>
                    <td>${i.variant_name || '—'}</td>
                    <td class="text-end">${i.quantity}</td>
                    <td class="text-end">${fmt(i.cost_price)}</td>
                    <td class="text-end">${fmt(i.selling_price)}</td>
                    <td class="text-end">${fmt(i.tax)}</td>
                </tr>
            `).join('');

            document.getElementById('pm-loading').style.display = 'none';
            document.getElementById('pm-content').style.display  = '';
        });
}
</script>
@endpush
@endsection
