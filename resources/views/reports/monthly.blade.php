@extends('layouts.app')
@section('title', 'Báo cáo Doanh số')
@section('breadcrumb', 'Báo cáo / Doanh số')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
.report-table th, .report-table td { font-size: .8rem; padding: .45rem .6rem; }
.report-table .col-money { text-align: right; font-family: 'Courier New', monospace; }
.profit-pos { color: #059669; font-weight: 700; }
.profit-neg { color: #dc2626; font-weight: 700; }
.flatpickr-input[readonly] { background: #fff; }
</style>
@endpush

@section('content')
{{-- Header + filters --}}
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0 fw-bold">Báo cáo Doanh số</h5>
        <small class="text-muted">
            {{ $shops->firstWhere('id', $shopId)?->name ?? '—' }}
            &nbsp;·&nbsp;
            {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
            @if(count($productIds) > 0)
                &nbsp;·&nbsp; {{ count($productIds) }} sản phẩm được chọn
            @endif
        </small>
    </div>
    <form method="GET" class="d-flex gap-2 align-items-end flex-wrap" id="reportFilterForm">
        <div>
            <label class="form-label mb-1 fw-semibold" style="font-size:.75rem">Shop</label>
            <select name="shop_id" class="form-select form-select-sm" style="width:180px" onchange="this.form.submit()">
                @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ $s->id == $shopId ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label mb-1 fw-semibold" style="font-size:.75rem">Từ ngày</label>
            <input type="text" name="date_from" id="dateFrom" class="form-control form-control-sm"
                   value="{{ $dateFrom }}" style="width:130px" placeholder="dd/mm/yyyy" readonly>
        </div>
        <div>
            <label class="form-label mb-1 fw-semibold" style="font-size:.75rem">Đến ngày</label>
            <input type="text" name="date_to" id="dateTo" class="form-control form-control-sm"
                   value="{{ $dateTo }}" style="width:130px" placeholder="dd/mm/yyyy" readonly>
        </div>
        @if($products->isNotEmpty())
        <div>
            <label class="form-label mb-1 fw-semibold" style="font-size:.75rem">Sản phẩm</label>
            <select name="product_ids[]" id="productSelect" class="form-select form-select-sm" multiple
                    style="min-width:220px">
                @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ in_array($p->id, $productIds) ? 'selected' : '' }}>
                        {{ $p->name }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif
        <button type="submit" class="btn btn-sm btn-primary align-self-end">
            <i class="bi bi-search"></i> Xem
        </button>
        @if(request()->hasAny(['date_from', 'date_to', 'product_ids']))
            <a href="{{ route('reports.monthly', ['shop_id' => $shopId]) }}"
               class="btn btn-sm btn-outline-secondary align-self-end">
                <i class="bi bi-x"></i>
            </a>
        @endif
    </form>
</div>

{{-- Summary cards --}}
<div class="row g-2 mb-3">
    <div class="col-auto">
        <div class="stat-card" style="background:#3b82f6">
            <div class="label">Doanh số</div>
            <div class="value">{{ number_format($report['total_revenue']) }}₫</div>
        </div>
    </div>
    <div class="col-auto">
        <div class="stat-card" style="background:#7c3aed">
            <div class="label">Giá vốn</div>
            <div class="value">{{ number_format($report['total_cost']) }}₫</div>
        </div>
    </div>
    <div class="col-auto">
        <div class="stat-card" style="background:{{ $report['total_profit'] >= 0 ? '#059669' : '#dc2626' }}">
            <div class="label">Lợi nhuận *</div>
            <div class="value">{{ number_format($report['total_profit']) }}₫</div>
        </div>
    </div>
    <div class="col-auto">
        <div class="stat-card" style="background:#f59e0b">
            <div class="label">Số lượng</div>
            <div class="value">{{ number_format($report['total_qty']) }}</div>
        </div>
    </div>
</div>
<small class="text-muted d-block mb-3" style="font-size:.72rem">
    * Lợi nhuận = Doanh số − Thuế 1.5% − Giá vốn (chưa trừ phí vận hành đơn hàng, ADS, KOL)
</small>

{{-- Chart --}}
@if($report['products']->isNotEmpty())
<div class="card mb-3">
    <div class="card-header fw-semibold d-flex align-items-center justify-content-between">
        <span style="font-size:.85rem">
            <i class="bi bi-bar-chart-line me-1 text-primary"></i>Biểu đồ theo sản phẩm
        </span>
        <div class="btn-group btn-group-sm" id="chartTypeToggle">
            <button class="btn btn-outline-secondary active" data-type="revenue">Doanh số</button>
            <button class="btn btn-outline-secondary" data-type="profit">Lợi nhuận</button>
        </div>
    </div>
    <div class="card-body" style="height:{{ min(60 + $report['products']->count() * 28, 360) }}px; padding:.75rem">
        <canvas id="productChart"></canvas>
    </div>
</div>
@endif

{{-- Product table --}}
<div class="card">
    <div class="card-header fw-semibold" style="font-size:.85rem">
        Chi tiết theo sản phẩm
        <span class="badge bg-secondary ms-1">{{ $report['products']->count() }}</span>
    </div>
    <div class="table-responsive">
        <table class="table report-table mb-0 table-hover">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th class="col-money">Số lượng</th>
                    <th class="col-money">Doanh số</th>
                    <th class="col-money">Giá vốn</th>
                    <th class="col-money">Lợi nhuận *</th>
                    <th class="col-money">% LN/DS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['products'] as $p)
                @php
                    $pct = $p->total_revenue > 0
                        ? round($p->total_profit / $p->total_revenue * 100, 1)
                        : 0;
                @endphp
                <tr>
                    <td class="fw-semibold">{{ $p->product_name }}</td>
                    <td class="col-money">{{ number_format($p->total_qty) }}</td>
                    <td class="col-money">{{ number_format($p->total_revenue) }}₫</td>
                    <td class="col-money">{{ number_format($p->total_cost) }}₫</td>
                    <td class="col-money {{ $p->total_profit >= 0 ? 'profit-pos' : 'profit-neg' }}">
                        {{ number_format($p->total_profit) }}₫
                    </td>
                    <td class="col-money {{ $pct >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $pct }}%
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                        Không có dữ liệu trong khoảng thời gian này.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($report['products']->isNotEmpty())
            <tfoot class="table-light">
                @php
                    $totalPct = $report['total_revenue'] > 0
                        ? round($report['total_profit'] / $report['total_revenue'] * 100, 1)
                        : 0;
                @endphp
                <tr>
                    <th>Tổng</th>
                    <th class="col-money">{{ number_format($report['total_qty']) }}</th>
                    <th class="col-money">{{ number_format($report['total_revenue']) }}₫</th>
                    <th class="col-money">{{ number_format($report['total_cost']) }}₫</th>
                    <th class="col-money {{ $report['total_profit'] >= 0 ? 'profit-pos' : 'profit-neg' }}">
                        {{ number_format($report['total_profit']) }}₫
                    </th>
                    <th class="col-money {{ $totalPct >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $totalPct }}%
                    </th>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>
<script>
const fpOpts = { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', allowInput: true, locale: 'vn' };
flatpickr('#dateFrom', fpOpts);
flatpickr('#dateTo',   fpOpts);

@if($products->isNotEmpty())
new TomSelect('#productSelect', {
    plugins       : { remove_button: {} },
    placeholder   : 'Tất cả sản phẩm (để trống = lấy hết)...',
    maxOptions    : null,
    dropdownParent: 'body',
    render: {
        no_results: () => '<div class="px-3 py-2 text-muted" style="font-size:.78rem">Không tìm thấy</div>',
    },
});
@endif
</script>
@if($report['products']->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const productLabels  = @json($report['products']->pluck('product_name'));
const dataRevenue    = @json($report['products']->pluck('total_revenue'));
const dataCost       = @json($report['products']->pluck('total_cost'));
const dataProfit     = @json($report['products']->pluck('total_profit'));

const ctx = document.getElementById('productChart').getContext('2d');

function buildDatasets(type) {
    if (type === 'profit') {
        return [{
            label: 'Lợi nhuận',
            data: dataProfit,
            backgroundColor: dataProfit.map(v => v >= 0 ? 'rgba(16,185,129,.75)' : 'rgba(220,38,38,.65)'),
            borderRadius: 3,
        }];
    }
    return [
        {
            label: 'Doanh số',
            data: dataRevenue,
            backgroundColor: 'rgba(99,102,241,.7)',
            borderRadius: 3,
        },
        {
            label: 'Giá vốn',
            data: dataCost,
            backgroundColor: 'rgba(124,58,237,.55)',
            borderRadius: 3,
        },
    ];
}

let chart = new Chart(ctx, {
    type: 'bar',
    data: { labels: productLabels, datasets: buildDatasets('revenue') },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top', labels: { font: { size: 11 }, boxWidth: 12 } },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.dataset.label}: ${Math.round(ctx.raw).toLocaleString('vi-VN')}₫`
                }
            }
        },
        scales: {
            x: {
                ticks: {
                    font: { size: 10 },
                    callback: v => (v / 1_000_000).toLocaleString('vi-VN') + 'M'
                },
                grid: { color: 'rgba(0,0,0,.05)' }
            },
            y: { ticks: { font: { size: 10 } } }
        }
    }
});

document.getElementById('chartTypeToggle').addEventListener('click', function (e) {
    const btn = e.target.closest('button[data-type]');
    if (!btn) return;
    this.querySelectorAll('button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    chart.data.datasets = buildDatasets(btn.dataset.type);
    chart.update();
});
</script>
@endif
@endpush
