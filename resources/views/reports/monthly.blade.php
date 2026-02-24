@extends('layouts.app')
@section('title', 'Báo cáo tháng ' . $month . '/' . $year)
@section('breadcrumb', 'Báo cáo / Tháng ' . $month . '/' . $year)

@push('styles')
<style>
.report-table th, .report-table td { font-size: .78rem; padding: .4rem .6rem; white-space: nowrap; }
.report-table .col-money { text-align: right; font-family: 'Courier New', monospace; }
.input-inline { width: 120px; font-size: .78rem; text-align: right; }
.input-refund  { width: 100px; font-size: .78rem; text-align: right; }
.profit-pos  { color: #059669; font-weight: 700; }
.profit-neg  { color: #dc2626; font-weight: 700; }
.row-today   { background: #fffbeb !important; }
.row-zero    { opacity: .55; }
.saving-indicator { font-size: .7rem; color: #6b7280; }
</style>
@endpush

@section('content')
{{-- Header + filters --}}
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0 fw-bold">Báo cáo Tháng {{ $month }}/{{ $year }}</h5>
        <small class="text-muted">{{ $shops->firstWhere('id', $shopId)?->name ?? 'Tất cả shop' }}</small>
    </div>
    <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
        <div>
            <label class="form-label mb-1 fw-semibold" style="font-size:.75rem">Shop</label>
            <select name="shop_id" class="form-select form-select-sm" style="width:180px" onchange="this.form.submit()">
                @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ $s->id == $shopId ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label mb-1 fw-semibold" style="font-size:.75rem">Tháng</label>
            <select name="month" class="form-select form-select-sm" style="width:80px" onchange="this.form.submit()">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>T{{ $m }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label class="form-label mb-1 fw-semibold" style="font-size:.75rem">Năm</label>
            <select name="year" class="form-select form-select-sm" style="width:90px" onchange="this.form.submit()">
                @foreach(range(now()->year, 2024) as $y)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <input type="hidden" name="shop_id" value="{{ $shopId }}">
    </form>
</div>

{{-- Monthly summary stats --}}
@php
    $totalProfitBeforeAds = collect($report['days'])->sum('profit_before_ads');
    $totalAdsCost         = collect($report['days'])->sum('ads_cost');
    $totalDailyProfit     = $report['total_daily_profit'];
    $monthlyProfit        = $report['monthly_profit'];
    $kolCost              = $report['kol_cost'];
@endphp
<div class="row g-2 mb-3">
    <div class="col-auto">
        <div class="stat-card" style="background:#8b5cf6">
            <div class="label">LN trước ADS</div>
            <div class="value">{{ number_format($totalProfitBeforeAds) }}₫</div>
        </div>
    </div>
    <div class="col-auto">
        <div class="stat-card" style="background:#f59e0b">
            <div class="label">Tổng chi phí ADS</div>
            <div class="value">{{ number_format($totalAdsCost) }}₫</div>
        </div>
    </div>
    <div class="col-auto">
        <div class="stat-card" style="background:#06b6d4">
            <div class="label">LN sau ADS</div>
            <div class="value">{{ number_format($totalDailyProfit) }}₫</div>
        </div>
    </div>
    <div class="col-auto">
        <div class="stat-card" style="background:#ef4444">
            <div class="label">Chi phí KOL</div>
            <div class="value">{{ number_format($kolCost) }}₫</div>
        </div>
    </div>
    <div class="col-auto">
        <div class="stat-card" style="background:{{ $monthlyProfit >= 0 ? '#059669' : '#dc2626' }}">
            <div class="label">LN Tháng</div>
            <div class="value">{{ number_format($monthlyProfit) }}₫</div>
        </div>
    </div>
</div>

{{-- Chart --}}
@php
    $chartDays    = collect($report['days'])->pluck('date')->toArray();
    $chartProfit  = collect($report['days'])->pluck('profit_before_ads')->toArray();
    $chartAds     = collect($report['days'])->pluck('ads_cost')->toArray();
    $chartNetProfit = collect($report['days'])->pluck('daily_profit')->toArray();
@endphp
<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span class="fw-semibold" style="font-size:.85rem">
            <i class="bi bi-bar-chart-line me-1 text-primary"></i>Biểu đồ lợi nhuận theo ngày
        </span>
        <div class="btn-group btn-group-sm" id="chartTypeToggle">
            <button class="btn btn-outline-secondary active" data-type="bar">Cột</button>
            <button class="btn btn-outline-secondary" data-type="line">Đường</button>
        </div>
    </div>
    <div class="card-body" style="height:260px; padding:.75rem">
        <canvas id="monthlyChart"></canvas>
    </div>
</div>

{{-- KOL Cost form --}}
<div class="card mb-3">
    <div class="card-body py-2 d-flex align-items-center gap-3 flex-wrap">
        <span class="fw-semibold" style="font-size:.85rem">
            <i class="bi bi-star me-1 text-warning"></i>Chi phí KOL Tháng {{ $month }}/{{ $year }}:
        </span>
        <form action="{{ route('reports.monthly-kol.update') }}" method="POST"
              class="d-flex align-items-center gap-2" id="kolForm">
            @csrf
            <input type="hidden" name="shop_id" value="{{ $shopId }}">
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="number" name="kol_cost" id="kolCostInput"
                   class="form-control form-control-sm" style="width:160px"
                   value="{{ $kolCost }}" min="0" placeholder="0">
            <button type="submit" class="btn btn-sm btn-warning">
                <i class="bi bi-check-lg"></i> Lưu KOL
            </button>
            <span class="saving-indicator" id="kolSaved"></span>
        </form>
    </div>
</div>

{{-- Daily table --}}
<div class="card">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        <span>Chi tiết theo ngày</span>
        <small class="text-muted">ADS nhập theo định dạng ₫324.431 hoặc số thuần (tự nhân 1.08)</small>
    </div>
    <div class="table-responsive">
        <table class="table report-table mb-0">
            <thead>
                <tr>
                    <th>Ngày</th>
                    <th class="col-money">LN trước ADS</th>
                    <th></th>
                    <th style="min-width:130px">ADS (Nhập tay)</th>
                    <th style="min-width:100px">Hoàn ADS</th>
                    <th class="col-money">Chi phí ADS</th>
                    <th class="col-money">Lợi nhuận</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @php $today = now()->format('Y-m-d'); @endphp
                @foreach($report['days'] as $day)
                @php
                    $isToday   = $day['date'] === $today;
                    $isZero    = $day['profit_before_ads'] == 0;
                    $rowClass  = $isToday ? 'row-today' : ($isZero ? 'row-zero' : '');
                    $profit    = $day['daily_profit'];
                @endphp
                <tr class="{{ $rowClass }}" data-date="{{ $day['date'] }}">
                    <td>
                        <span class="fw-semibold">{{ \Carbon\Carbon::parse($day['date'])->format('d/m') }}</span>
                        @if($isToday) <span class="badge bg-warning-subtle text-warning" style="font-size:.65rem">Hôm nay</span> @endif
                    </td>
                    <td class="col-money">
                        <span class="{{ $day['profit_before_ads'] >= 0 ? 'profit-pos' : 'profit-neg' }}">
                            {{ number_format($day['profit_before_ads']) }}
                        </span>
                    </td>
                    <td class="text-muted" style="font-size:.75rem; color:#94a3b8 !important">→</td>
                    {{-- ADS input --}}
                    <td>
                        <input type="text"
                               class="form-control form-control-sm input-inline ads-input"
                               data-date="{{ $day['date'] }}"
                               value="{{ $day['ads_raw_input'] ?? '' }}"
                               placeholder="₫0">
                    </td>
                    {{-- Hoàn ADS --}}
                    <td>
                        <input type="number"
                               class="form-control form-control-sm input-refund refund-input"
                               data-date="{{ $day['date'] }}"
                               value="{{ $day['ads_refund'] > 0 ? $day['ads_refund'] : '' }}"
                               placeholder="0" min="0">
                    </td>
                    {{-- Chi phí ADS --}}
                    <td class="col-money ads-cost-cell" id="ads-cost-{{ str_replace('-', '', $day['date']) }}">
                        {{ number_format($day['ads_cost']) }}
                    </td>
                    {{-- Lợi nhuận ngày --}}
                    <td class="col-money profit-cell" id="profit-{{ str_replace('-', '', $day['date']) }}">
                        <span class="{{ $profit >= 0 ? 'profit-pos' : 'profit-neg' }}">
                            {{ number_format($profit) }}
                        </span>
                    </td>
                    <td>
                        <span class="saving-indicator" id="saved-{{ str_replace('-', '', $day['date']) }}"></span>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th>Tổng tháng</th>
                    <th class="col-money profit-pos">{{ number_format($totalProfitBeforeAds) }}</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th class="col-money text-warning fw-bold">{{ number_format($totalAdsCost) }}</th>
                    <th class="col-money {{ $totalDailyProfit >= 0 ? 'profit-pos' : 'profit-neg' }}">
                        {{ number_format($totalDailyProfit) }}
                    </th>
                    <th></th>
                </tr>
                <tr class="table-success">
                    <th colspan="6" class="text-end">Lợi nhuận tháng (sau KOL {{ number_format($kolCost) }}₫):</th>
                    <th class="col-money {{ $monthlyProfit >= 0 ? 'profit-pos' : 'profit-neg' }}" id="monthlyProfitCell">
                        {{ number_format($monthlyProfit) }}
                    </th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const CSRF   = document.querySelector('meta[name="csrf-token"]').content;
const shopId = {{ $shopId }};
let saveTimer = {};

// ─── Parse ADS input (₫324.431 → 324431 * 1.08) ───────────────────────────
function parseAdsInput(raw) {
    if (!raw || raw.trim() === '') return 0;
    let cleaned = raw.replace(/[₫\s]/g, '');   // bỏ ký hiệu ₫
    cleaned = cleaned.replace(/\./g, '');        // dấu chấm = phân cách ngàn
    cleaned = cleaned.replace(/,/g, '.');        // phẩy = thập phân
    const val = parseFloat(cleaned) || 0;
    return Math.round(val * 1.08);
}

// ─── Save daily ADS via AJAX ───────────────────────────────────────────────
function saveDailyAds(date, adsRaw, adsRefund) {
    const key = date.replace(/-/g, '');
    const savedEl = document.getElementById('saved-' + key);
    if (savedEl) savedEl.textContent = 'Đang lưu...';

    fetch('{{ route("reports.daily-ads.update") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ shop_id: shopId, date, ads_raw_input: adsRaw, ads_refund: parseFloat(adsRefund) || 0 })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Cập nhật chi phí ADS
            const adsCostEl = document.getElementById('ads-cost-' + key);
            const profitEl  = document.getElementById('profit-' + key);
            if (adsCostEl) adsCostEl.textContent = formatVND(data.ads_cost);

            // Tính lại profit_before_ads cho dòng này (lấy từ DOM)
            const row = document.querySelector(`tr[data-date="${date}"]`);
            if (row && profitEl) {
                const pbaText = row.querySelector('.profit-pos, .profit-neg');
                // Không cần recalculate vì profit_before_ads không đổi
                // profit = profit_before_ads (từ server) - ads_cost mới
                // Ta cần reload hoặc server trả về
            }
            if (savedEl) { savedEl.textContent = '✓ Đã lưu'; setTimeout(() => savedEl.textContent = '', 2000); }
        }
    })
    .catch(() => { if (savedEl) savedEl.textContent = '✗ Lỗi'; });
}

// ─── Debounced save on input ───────────────────────────────────────────────
document.querySelectorAll('.ads-input, .refund-input').forEach(function (input) {
    input.addEventListener('input', function () {
        const date    = this.dataset.date;
        const row     = document.querySelector(`tr[data-date="${date}"]`);
        const adsRaw  = row.querySelector('.ads-input').value;
        const refund  = row.querySelector('.refund-input').value;

        // Live preview chi phí ADS
        const adsFee    = parseAdsInput(adsRaw);
        const adsRefund = parseFloat(refund) || 0;
        const adsCost   = Math.max(0, adsFee - adsRefund);
        const key = date.replace(/-/g, '');
        const adsCostEl = document.getElementById('ads-cost-' + key);
        if (adsCostEl) adsCostEl.textContent = formatVND(adsCost);

        // Debounce save 800ms
        clearTimeout(saveTimer[date]);
        saveTimer[date] = setTimeout(() => saveDailyAds(date, adsRaw, refund), 800);
    });
});

// ─── KOL form AJAX ────────────────────────────────────────────────────────
document.getElementById('kolForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const fd = new FormData(this);
    fetch(this.action, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('kolSaved').textContent = '✓ Đã lưu';
            setTimeout(() => document.getElementById('kolSaved').textContent = '', 2000);
            // Reload để cập nhật monthly profit
            setTimeout(() => location.reload(), 500);
        }
    });
});

// ─── Chart.js biểu đồ lợi nhuận theo ngày ────────────────────────────────
const labels      = @json($chartDays);
const dataProfit  = @json($chartProfit);
const dataAds     = @json($chartAds);
const dataNet     = @json($chartNetProfit);

const ctx = document.getElementById('monthlyChart').getContext('2d');
let chart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels,
        datasets: [
            {
                label: 'LN trước ADS',
                data: dataProfit,
                backgroundColor: 'rgba(99,102,241,.65)',
                borderColor: '#6366f1',
                borderWidth: 1,
                borderRadius: 3,
            },
            {
                label: 'Chi phí ADS',
                data: dataAds,
                backgroundColor: 'rgba(245,158,11,.55)',
                borderColor: '#f59e0b',
                borderWidth: 1,
                borderRadius: 3,
            },
            {
                label: 'LN sau ADS',
                data: dataNet,
                backgroundColor: 'rgba(16,185,129,.65)',
                borderColor: '#10b981',
                borderWidth: 1,
                borderRadius: 3,
            },
        ]
    },
    options: {
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
            y: {
                ticks: {
                    font: { size: 10 },
                    callback: v => (v / 1000).toLocaleString('vi-VN') + 'K'
                },
                grid: { color: 'rgba(0,0,0,.05)' }
            },
            x: { ticks: { font: { size: 10 }, maxRotation: 45 } }
        }
    }
});

// Toggle bar / line
document.getElementById('chartTypeToggle').addEventListener('click', function (e) {
    const btn = e.target.closest('button[data-type]');
    if (!btn) return;
    this.querySelectorAll('button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    chart.config.type = btn.dataset.type;
    chart.update();
});
</script>
@endpush

