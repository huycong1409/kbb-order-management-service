@extends('layouts.app')
@section('title', 'Dashboard')
@section('breadcrumb', 'Tổng quan')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<style>
.delta-badge { font-size: .7rem; padding: 2px 6px; border-radius: 20px; }
.delta-up   { background: #dcfce7; color: #166534; }
.delta-down { background: #fee2e2; color: #991b1b; }
.delta-eq   { background: #f1f5f9; color: #64748b; }
.top-bar { height: 8px; border-radius: 4px; background: linear-gradient(90deg,#6366f1,#8b5cf6); }
</style>
@endpush

@section('content')

{{-- Filter --}}
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <h5 class="mb-0 fw-bold me-2">Tổng quan</h5>
    <form method="GET" class="d-flex gap-2 align-items-end">
        <select name="shop_id" class="form-select form-select-sm" style="width:180px" onchange="this.form.submit()">
            <option value="0" {{ $shopId == 0 ? 'selected' : '' }}>Tất cả Shop</option>
            @foreach($shops as $s)
                <option value="{{ $s->id }}" {{ $s->id == $shopId ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
        </select>
        <select name="month" class="form-select form-select-sm" style="width:80px" onchange="this.form.submit()">
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>T{{ $m }}</option>
            @endfor
        </select>
        <select name="year" class="form-select form-select-sm" style="width:90px" onchange="this.form.submit()">
            @foreach(range(now()->year, 2024) as $y)
                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
    </form>
</div>

{{-- Stat cards --}}
@php
    $profitDelta  = $prevProfit  > 0 ? round(($totalProfit  - $prevProfit)  / $prevProfit  * 100, 1) : null;
    $sellingDelta = $prevSelling > 0 ? round(($totalSelling - $prevSelling) / $prevSelling * 100, 1) : null;
@endphp
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-card h-100" style="background:#3b82f6">
            <div class="label">Tổng đơn T{{ $month }}/{{ $year }}</div>
            <div class="value">{{ number_format($totalOrders) }}</div>
            @if($lossOrders > 0)
                <div style="font-size:.7rem;opacity:.85;margin-top:.3rem">
                    <i class="bi bi-exclamation-triangle-fill"></i> {{ $lossOrders }} đơn lỗ
                </div>
            @endif
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card h-100" style="background:#8b5cf6">
            <div class="label">Doanh thu</div>
            <div class="value">{{ number_format($totalSelling / 1000000, 1) }}M₫</div>
            @if($sellingDelta !== null)
                <span class="delta-badge {{ $sellingDelta >= 0 ? 'delta-up' : 'delta-down' }} mt-1 d-inline-block">
                    {{ $sellingDelta >= 0 ? '▲' : '▼' }} {{ abs($sellingDelta) }}% vs T{{ $month-1 < 1 ? 12 : $month-1 }}
                </span>
            @endif
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card h-100" style="background:{{ $totalProfit >= 0 ? '#059669' : '#dc2626' }}">
            <div class="label">Lợi nhuận</div>
            <div class="value">{{ number_format($totalProfit / 1000000, 2) }}M₫</div>
            @if($profitDelta !== null)
                <span class="delta-badge {{ $profitDelta >= 0 ? 'delta-up' : 'delta-down' }} mt-1 d-inline-block">
                    {{ $profitDelta >= 0 ? '▲' : '▼' }} {{ abs($profitDelta) }}% vs T{{ $month-1 < 1 ? 12 : $month-1 }}
                </span>
            @endif
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card h-100" style="background:{{ $totalSelling > 0 ? '#0284c7' : '#64748b' }}">
            <div class="label">Tỷ suất LN</div>
            @php $margin = $totalSelling > 0 ? round($totalProfit / $totalSelling * 100, 1) : 0; @endphp
            <div class="value">{{ $margin }}%</div>
            <div style="font-size:.7rem;opacity:.8;margin-top:.3rem">
                so với doanh thu
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Chart lợi nhuận theo ngày --}}
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header fw-semibold" style="font-size:.85rem">
                <i class="bi bi-graph-up text-primary me-1"></i>Lợi nhuận theo ngày — T{{ $month }}/{{ $year }}
            </div>
            <div class="card-body" style="height:260px; padding:.75rem">
                <canvas id="dashChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Top sản phẩm bán chạy --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header fw-semibold" style="font-size:.85rem">
                <i class="bi bi-trophy text-warning me-1"></i>Top 10 sản phẩm bán chạy
            </div>
            <div class="card-body p-0">
                @php $maxQty = $topProductsQuery->max('total_qty') ?: 1; @endphp
                <ul class="list-unstyled mb-0">
                    @forelse($topProductsQuery as $i => $p)
                    <li class="px-3 py-2" style="border-bottom:1px solid #f1f5f9">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="d-flex align-items-center gap-2" style="min-width:0">
                                <span class="fw-bold text-muted" style="font-size:.7rem;width:16px">{{ $i+1 }}</span>
                                <span style="font-size:.78rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                    {{ Str::limit($p->product_name, 28) }}
                                </span>
                            </div>
                            <span class="fw-bold text-primary ms-2" style="font-size:.78rem;white-space:nowrap">
                                {{ number_format($p->total_qty) }} SP
                            </span>
                        </div>
                        <div class="top-bar" style="width:{{ round($p->total_qty / $maxQty * 100) }}%"></div>
                    </li>
                    @empty
                    <li class="text-center text-muted py-4" style="font-size:.8rem">Chưa có dữ liệu</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const dashLabels = @json($chartLabels);
const dashData   = @json($chartData);

const ctx = document.getElementById('dashChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: dashLabels,
        datasets: [{
            label: 'Lợi nhuận (₫)',
            data: dashData,
            backgroundColor: dashData.map(v => v >= 0 ? 'rgba(16,185,129,.7)' : 'rgba(239,68,68,.7)'),
            borderColor:     dashData.map(v => v >= 0 ? '#10b981' : '#ef4444'),
            borderWidth: 1,
            borderRadius: 3,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${Math.round(ctx.raw).toLocaleString('vi-VN')}₫`
                }
            }
        },
        scales: {
            y: {
                ticks: {
                    font: { size: 10 },
                    callback: v => (v/1000).toLocaleString('vi-VN') + 'K'
                },
                grid: { color: 'rgba(0,0,0,.05)' }
            },
            x: { ticks: { font: { size: 10 }, maxRotation: 45 } }
        }
    }
});
</script>
@endpush
@endsection
