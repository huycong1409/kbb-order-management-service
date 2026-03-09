@extends('layouts.app')
@section('title', 'Danh sách Đơn hàng')
@section('breadcrumb', 'Đơn hàng')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
/* ─── Table ────────────────────────────────────────────────────────────────── */
.orders-table { table-layout: auto; border-collapse: separate; border-spacing: 0; }
.orders-table th {
    white-space: nowrap;
    font-size: 1rem;
    padding: .4rem .55rem;
    background: #f1f5f9;
    border-bottom: 2px solid #cbd5e1;
    position: sticky;
    top: 0;
    z-index: 10;
}
.orders-table td { white-space: nowrap; font-size: 1.12rem; padding: .38rem .55rem; }

/* ─── Row grouping — left border indicator ──────────────────────────────────── */
.order-first-row > td {
    background: #fff;
    border-top: 2px solid #94a3b8;
    border-bottom: 0;
}
.order-first-row > td:first-child { border-left: 3px solid #3b82f6; }

.order-sub-row > td    { background: #fff; border-top: 0; border-bottom: 0; }
.order-sub-row > td:first-child { border-left: 3px solid #bfdbfe; }

.order-total-row > td {
    background: #fff;
    font-weight: 600;
    font-size: 1.12rem;
    border-top: 0;
    border-bottom: 0;
}

.order-loss-first > td {
    background: #fff;
    border-top: 2px solid #94a3b8;
    border-bottom: 0;
}
.order-loss-first > td:first-child { border-left: 3px solid #ef4444; }
.order-loss-sub > td   { background: #fff; border-top: 0; border-bottom: 0; }
.order-loss-sub > td:first-child { border-left: 3px solid #fca5a5; }

/* ─── Money columns ─────────────────────────────────────────────────────────── */
.col-money {
    text-align: right;
    font-size: 1.19rem;
    font-variant-numeric: tabular-nums;
}
.profit-positive { color: #059669; font-weight: 700; font-size: 1.26rem; }
.profit-negative { color: #dc2626; font-weight: 700; font-size: 1.26rem; }

/* ─── Sticky container (filter + stats + table header + pagination) ─────────── */
#stickyContainer {
    position: sticky;
    top: 44px; /* JS ghi đè */
    z-index: 90;
    display: flex;
    flex-direction: column;
    /* height set by JS = 100vh - topbarH */
}
#stickyContainer .card {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

/* ─── Sticky bar (filter + stats — không cần sticky riêng nữa) ─────────────── */
#stickyBar {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    box-shadow: 0 2px 4px rgba(0,0,0,.06);
    margin: 0 -1.5rem;
    padding: 0 1.5rem;
}
.filter-row { padding: .45rem 0; border-bottom: 1px solid #f0f4f8; }

/* ─── Stat bar ──────────────────────────────────────────────────────────────── */
.stat-bar {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    padding: .3rem 0;
}
.stat-bar .s-item { display: flex; flex-direction: column; }
.stat-bar .s-lbl { font-size: .67rem; color: #94a3b8; font-weight: 600; letter-spacing: .03em; text-transform: uppercase; }
.stat-bar .s-val { font-size: 1.05rem; font-weight: 700; font-variant-numeric: tabular-nums; }

/* ─── Scroll wrapper: flex:1 để tự fill khoảng còn lại, cuộn cả 2 chiều ─────── */
.table-scroll-wrapper { flex: 1; min-height: 0; overflow-x: auto; overflow-y: auto; -webkit-overflow-scrolling: touch; }

/* ─── Sort + copy ────────────────────────────────────────────────────────────── */
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

/* ─── Date separator row ─────────────────────────────────────────────────────── */
.date-sep-row > td {
    background: #fef3c7;
    border-top: 0 !important;
    border-bottom: 0 !important;
    padding: .5rem 1rem;
    text-align: center;
    font-size: 1rem;
    font-weight: 700;
    color: #92400e;
    letter-spacing: .06em;
    text-transform: uppercase;
}
/* ─── Date info row (tóm tắt ngày: lợi nhuận trước ADS / ADS / lợi nhuận) ───── */
.date-info-row > td {
    background: #fffbeb;
    border-top: 0 !important;
    border-bottom: 1px solid #fde68a !important;
    padding: .45rem 1rem .5rem;
}
.date-info-row .di-items {
    display: flex;
    gap: 1.6rem;
    justify-content: center;
    flex-wrap: wrap;
}
.date-info-row .di-item {
    display: flex;
    align-items: center;
    gap: .35rem;
    font-size: 1rem;
}
.date-info-row .di-lbl {
    color: #92400e;
    font-weight: 600;
    white-space: nowrap;
}
.date-info-row .di-val {
    font-weight: 700;
    font-size: 1.1rem;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

/* ─── Flatpickr ──────────────────────────────────────────────────────────────── */
.flatpickr-input[readonly] { background: #fff; }
</style>
@endpush

@section('content')
{{-- Page title (cuộn bình thường, không sticky) ─────────────────────────────── --}}
<div class="d-flex justify-content-between align-items-center mb-2">
    <div>
        <h5 class="mb-0 fw-bold">Danh sách Đơn hàng</h5>
        <small class="text-muted">
            {{ $orders->total() }} đơn hàng
            @if(request('product_name'))
                &nbsp;<span class="badge bg-primary-subtle text-primary" style="font-size:.7rem">
                    <i class="bi bi-funnel-fill me-1"></i>SP: "{{ request('product_name') }}"
                </span>
            @endif
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('orders.export', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-download"></i> Xuất Excel
        </a>
        <a href="{{ route('orders.import-form') }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel"></i> Import Excel
        </a>
    </div>
</div>

{{-- Sticky container: filter + stats + table header đều dính cùng nhau ───────── --}}
<div id="stickyContainer">
{{-- Sticky bar: bộ lọc + chỉ số ────────────────────────────────────────────── --}}
<div id="stickyBar">
    <form method="GET" class="filter-row d-flex flex-wrap gap-2 align-items-end" id="filterForm">
        <div>
            <label class="form-label mb-1 fw-semibold" style="font-size:.72rem">Shop</label>
            <select name="shop_id" class="form-select form-select-sm" style="width:170px">
                <option value="">Tất cả Shop</option>
                @foreach($shops as $shop)
                    <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>
                        {{ $shop->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label mb-1 fw-semibold" style="font-size:.72rem">Từ ngày</label>
            <input type="text" name="date_from" id="dateFrom" class="form-control form-control-sm"
                   value="{{ request('date_from') }}" style="width:120px" placeholder="dd/mm/yyyy" readonly>
        </div>
        <div>
            <label class="form-label mb-1 fw-semibold" style="font-size:.72rem">Đến ngày</label>
            <input type="text" name="date_to" id="dateTo" class="form-control form-control-sm"
                   value="{{ request('date_to') }}" style="width:120px" placeholder="dd/mm/yyyy" readonly>
        </div>
        <div>
            <label class="form-label mb-1 fw-semibold" style="font-size:.72rem">Tên sản phẩm</label>
            <input type="text" name="product_name" class="form-control form-control-sm"
                   value="{{ request('product_name') }}" placeholder="VD: lót ly, khay..." style="width:180px">
        </div>
        <div>
            <label class="form-label mb-1 fw-semibold" style="font-size:.72rem">Tìm kiếm</label>
            <input type="text" name="search" class="form-control form-control-sm"
                   value="{{ request('search') }}" placeholder="Mã đơn, tên SP..." style="width:160px">
        </div>
        <div class="align-self-end">
            <div class="form-check form-switch mb-0" style="padding-top:.3rem">
                <input class="form-check-input" type="checkbox" name="loss_only" id="lossOnly"
                       value="1" {{ request('loss_only') ? 'checked' : '' }} onchange="this.form.submit()">
                <label class="form-check-label text-danger fw-semibold" for="lossOnly" style="font-size:.8rem">Đơn lỗ</label>
            </div>
        </div>
        <div class="d-flex gap-1 align-self-end">
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-search"></i> Lọc
            </button>
            @if(request()->hasAny(['shop_id','date_from','date_to','search','product_id','product_name','loss_only']))
                <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x"></i>
                </a>
            @endif
        </div>
    </form>
    @php
        $totalSelling = $orders->getCollection()->flatMap->items->sum('selling_price');
        $totalProfit  = $orders->getCollection()->sum(fn($o) => $o->profit);
    @endphp
    <div class="stat-bar">
        <div class="s-item">
            <span class="s-lbl">Đơn (trang này)</span>
            <span class="s-val" style="color:#3b82f6">{{ $orders->count() }}</span>
        </div>
        <div class="s-item">
            <span class="s-lbl">Tổng giá bán</span>
            <span class="s-val" style="color:#7c3aed">{{ number_format($totalSelling) }}₫</span>
        </div>
        <div class="s-item">
            <span class="s-lbl">Lợi nhuận</span>
            <span class="s-val {{ $totalProfit >= 0 ? 'profit-positive' : 'profit-negative' }}">
                {{ number_format($totalProfit) }}₫
            </span>
        </div>
    </div>
</div>

{{-- Table card (nằm trong #stickyContainer, pagination ở dưới cùng) ──────────── --}}
<div class="card mt-2 mb-0">
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
                    <th class="col-money">Phí CĐ</th>
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
                @php $prevDate = null; @endphp
                @forelse($orders as $order)
                    @php
                        $items       = $order->items;
                        $itemCount   = $items->count();
                        $orderProfit = $order->profit;
                        $isLoss      = $orderProfit < 0;
                        $orderDay    = $order->order_date->format('Y-m-d');
                    @endphp

                    {{-- Date separator khi sang ngày mới --}}
                    @if($orderDay !== $prevDate)
                    @php
                        $prevDate = $orderDay;
                        $ds = $dailyStats[$orderDay] ?? null;
                    @endphp
                    <tr class="date-sep-row">
                        <td colspan="15">
                            <i class="bi bi-calendar3 me-1"></i>
                            {{ $order->order_date->locale('vi')->isoFormat('dddd, DD/MM/YYYY') }}
                        </td>
                    </tr>
                    @if($ds)
                    @php
                        $dsProfit    = $ds['profit_before_ads'];
                        $dsAds       = $ds['ads_cost'];
                        $dsNet       = $ds['profit'];
                        $netClass    = $dsNet >= 0 ? 'profit-positive' : 'profit-negative';
                        $profClass   = $dsProfit >= 0 ? 'profit-positive' : 'profit-negative';
                    @endphp
                    <tr class="date-info-row">
                        <td colspan="15">
                            <div class="di-items">
                                <span class="di-item">
                                    <span class="di-lbl">LN trước ADS:</span>
                                    <span class="di-val {{ $profClass }}">{{ number_format($dsProfit) }}₫</span>
                                </span>
                                <span class="di-item">
                                    <span class="di-lbl">Chi phí ADS:</span>
                                    <span class="di-val text-danger">{{ number_format($dsAds) }}₫</span>
                                </span>
                                <span class="di-item">
                                    <span class="di-lbl">Lợi nhuận:</span>
                                    <span class="di-val {{ $netClass }}">{{ number_format($dsNet) }}₫</span>
                                </span>
                            </div>
                        </td>
                    </tr>
                    @endif
                    @endif

                    @foreach($items as $idx => $item)
                    @php
                        $isFirst  = ($idx === 0);
                        $rowClass = $isFirst
                            ? ($isLoss ? 'order-loss-first' : 'order-first-row')
                            : ($isLoss ? 'order-loss-sub'   : 'order-sub-row');
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td>
                            @if($isFirst)
                                <div class="d-flex align-items-center gap-1">
                                    <a href="#" onclick="openPreview({{ $order->id }}, '{{ route('orders.show', $order->id) }}'); return false;"
                                       class="fw-semibold text-decoration-none text-primary" style="font-size:1.09rem"
                                       title="Xem nhanh">{{ $order->order_code }}</a>
                                    <button type="button" class="btn-copy" title="Copy mã đơn"
                                            onclick="copyCode('{{ $order->order_code }}', this)">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            @endif
                        </td>
                        <td style="font-size:1.09rem; color:#64748b">{{ $isFirst ? $order->order_date->format('d/m/Y H:i') : '' }}</td>
                        <td>
                            @if($isFirst)
                                <span class="badge bg-secondary-subtle text-secondary" style="font-size:.91rem; font-weight:600">
                                    {{ $order->shop->name ?? '—' }}
                                </span>
                            @endif
                        </td>
                        <td style="max-width:260px; white-space:normal; font-size:1.09rem">{{ $item->product_name }}</td>
                        <td style="font-size:1.09rem; color:#64748b">{{ $item->variant_name ?? '—' }}</td>
                        <td class="col-money">{{ $item->quantity }}</td>
                        <td class="col-money text-secondary">{{ number_format($item->effective_cost_price) }}</td>
                        <td class="col-money fw-semibold">{{ number_format($item->selling_price) }}</td>
                        <td class="col-money text-muted">{{ $isFirst ? number_format($order->fixed_fee) : '' }}</td>
                        <td class="col-money text-muted">{{ $isFirst ? number_format($order->service_fee) : '' }}</td>
                        <td class="col-money text-muted">{{ $isFirst ? number_format($order->payment_fee) : '' }}</td>
                        <td class="col-money text-muted">{{ $isFirst ? number_format($order->pi_ship) : '' }}</td>
                        <td class="col-money text-muted">{{ number_format($item->tax) }}</td>
                        <td class="col-money text-secondary">{{ number_format($item->total_cost) }}</td>
                        <td class="col-money">
                            @if($isFirst)
                                <span class="{{ $orderProfit >= 0 ? 'profit-positive' : 'profit-negative' }}">
                                    {{ number_format($orderProfit) }}
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach

                    @if($itemCount > 1)
                    <tr class="order-total-row">
                        <td colspan="7" class="text-end text-muted" style="font-size:1.05rem">
                            Tổng {{ $itemCount }} sản phẩm:
                        </td>
                        <td class="col-money">{{ number_format($order->total_selling_price) }}</td>
                        <td class="col-money text-muted">{{ number_format($order->fixed_fee) }}</td>
                        <td class="col-money text-muted">{{ number_format($order->service_fee) }}</td>
                        <td class="col-money text-muted">{{ number_format($order->payment_fee) }}</td>
                        <td class="col-money text-muted">{{ number_format($order->pi_ship) }}</td>
                        <td class="col-money text-muted">{{ number_format($order->total_tax) }}</td>
                        <td class="col-money text-secondary">{{ number_format($order->total_cost) }}</td>
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
    {{-- Pagination nằm trong card, luôn hiển thị ở dưới bảng ──────────────────── --}}
    <div class="card-footer bg-white flex-shrink-0">
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
</div>{{-- đóng #stickyContainer --}}

{{-- Drawer xem nhanh ──────────────────────────────────────────────────────── --}}
<div id="drawerOverlay" onclick="closeDrawer()"
     style="display:none; position:fixed; inset:0; z-index:1040; background:rgba(0,0,0,.35)"></div>

<div id="orderDrawer"
     style="position:fixed; top:0; right:-520px; width:520px; max-width:100vw; height:100vh;
            z-index:1050; background:#fff; box-shadow:-4px 0 24px rgba(0,0,0,.18);
            transition:right .22s ease; display:flex; flex-direction:column; overflow:hidden">
    <div class="d-flex align-items-center justify-content-between px-3 py-2" style="background:#1e293b; flex-shrink:0">
        <h6 class="mb-0 text-white">
            <i class="bi bi-receipt me-1"></i>
            <span id="pm-code">—</span>
            <span class="badge bg-secondary ms-2" id="pm-shop" style="font-size:.65rem"></span>
        </h6>
        <button onclick="closeDrawer()" class="btn-close btn-close-white" style="font-size:.75rem"></button>
    </div>
    <div style="flex:1; overflow-y:auto">
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
                <span class="fw-bold">LN: <strong id="pm-profit"></strong></span>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 justify-content-end px-3 py-2" style="border-top:1px solid #e2e8f0; flex-shrink:0">
        <a id="pm-detail-link" href="#" class="btn btn-sm btn-primary">
            <i class="bi bi-arrow-right-circle"></i> Xem chi tiết
        </a>
        <button onclick="closeDrawer()" class="btn btn-sm btn-outline-secondary">Đóng</button>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>
<script>
// ── Flatpickr date inputs ──────────────────────────────────────────────────────
const fpOpts = { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', allowInput: true, locale: 'vn' };
flatpickr('#dateFrom', fpOpts);
flatpickr('#dateTo',   fpOpts);

// ── Sticky layout: stickyContainer chiếm đúng phần còn lại của viewport ────────
document.addEventListener('DOMContentLoaded', () => {
    const topbar    = document.querySelector('.topbar');
    const container = document.getElementById('stickyContainer');
    if (!topbar || !container) return;

    function updateLayout() {
        const topH = topbar.offsetHeight;
        container.style.top    = topH + 'px';
        container.style.height = (window.innerHeight - topH) + 'px';
    }

    updateLayout();
    window.addEventListener('resize', updateLayout, { passive: true });
});

// ── Copy mã đơn ───────────────────────────────────────────────────────────────
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

// ── Drawer xem nhanh ──────────────────────────────────────────────────────────
function fmt(n) {
    if (n === '' || n === null || n === undefined) return '—';
    return Math.round(Number(n)).toLocaleString('vi-VN');
}
function closeDrawer() {
    document.getElementById('orderDrawer').style.right = '-520px';
    document.getElementById('drawerOverlay').style.display = 'none';
}
function openPreview(id, detailUrl) {
    document.getElementById('pm-loading').style.display = '';
    document.getElementById('pm-content').style.display = 'none';
    document.getElementById('pm-detail-link').href = detailUrl;
    document.getElementById('drawerOverlay').style.display = '';
    document.getElementById('orderDrawer').style.right = '0';

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
            profitEl.className   = d.profit >= 0 ? 'profit-positive' : 'profit-negative';

            document.getElementById('pm-items').innerHTML = d.items.map(i => `
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
            document.getElementById('pm-content').style.display = '';
        });
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });
</script>
@endpush
