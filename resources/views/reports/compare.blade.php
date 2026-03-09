@extends('layouts.app')
@section('title', 'So sánh chỉ số')
@section('breadcrumb', 'Báo cáo / So sánh chỉ số')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
/* ─── Item card ──────────────────────────────────────────────────────────────── */
.cmp-card {
    border: 1px solid #e2e8f0;
    border-radius: .4rem;
    overflow: hidden;
    background: #fff;
    transition: box-shadow .15s;
}
.cmp-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,.07); }

/* Header — always visible, click to toggle */
.cmp-header {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .4rem .6rem;
    cursor: pointer;
    user-select: none;
    border-bottom: 1px solid transparent;
    transition: border-color .15s;
}
.cmp-card.is-open .cmp-header { border-bottom-color: #f1f5f9; }

.cmp-toggle-icon {
    font-size: .65rem;
    color: #94a3b8;
    transition: transform .2s ease;
    flex-shrink: 0;
}
.cmp-card.is-open .cmp-toggle-icon { transform: rotate(90deg); }

.cmp-tag {
    font-size: .68rem;
    font-weight: 700;
    padding: .1rem .45rem;
    border-radius: .25rem;
    color: #fff;
    flex-shrink: 0;
}

.cmp-summary {
    font-size: .74rem;
    color: #64748b;
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.cmp-metrics {
    display: flex;
    gap: .6rem;
    font-size: .73rem;
    flex-shrink: 0;
}
.cmp-metrics .m-ds  { color: #3b82f6; font-weight: 700; }
.cmp-metrics .m-ln  { font-weight: 700; }
.cmp-metrics .pos   { color: #059669; }
.cmp-metrics .neg   { color: #dc2626; }

/* Body — collapsible */
.cmp-body {
    max-height: 0;
    overflow: hidden;
    transition: max-height .28s ease;
}
.cmp-card.is-open .cmp-body { max-height: 2400px; }

.cmp-inner { padding: .6rem; }

/* Stats bar */
.stats-row { font-size: .77rem; border-top: 1px solid #f1f5f9; padding-top: .5rem; margin-top: .5rem; }
.stats-row .lbl { font-size: .62rem; color: #94a3b8; font-weight: 600; letter-spacing: .03em; display:block; }
.stats-row .val { font-size: .82rem; font-weight: 700; }

/* Product table */
.pd-tbl th, .pd-tbl td { font-size: .72rem; padding: .28rem .4rem; white-space: nowrap; }
.pd-tbl .r { text-align: right; font-variant-numeric: tabular-nums; }
.pp { color: #059669; font-weight: 700; }
.pn { color: #dc2626; font-weight: 700; }

/* details toggle */
details > summary { list-style: none; cursor: pointer; }
details > summary::-webkit-details-marker { display: none; }

/* ─── Tom Select overrides ─────────────────────────────────────────────────── */
.cmp-inner .ts-wrapper { margin: 0; }
.cmp-inner .ts-control {
    font-size: .78rem;
    min-height: 32px;
    padding: .2rem .35rem;
    gap: .2rem;
}
.cmp-inner .ts-control input { font-size: .78rem; }
.cmp-inner .ts-dropdown { font-size: .78rem; }
.cmp-inner .ts-dropdown-content { max-height: 180px; }
.cmp-inner .ts-dropdown .option { padding: .3rem .55rem; }
.cmp-inner .item {
    font-size: .72rem;
    padding: .05rem .3rem;
    background: #e0e7ff;
    color: #3730a3;
    border-color: #c7d2fe;
}
.cmp-inner .item .remove { color: #6366f1; }

/* ─── Flatpickr overrides ──────────────────────────────────────────────────── */
.flatpickr-input[readonly] { background: #fff; }
.cmp-inner .flatpickr-input.form-control-sm { width: 110px !important; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">So sánh chỉ số</h5>
        <small class="text-muted">Bấm <i class="bi bi-chevron-right" style="font-size:.7rem"></i> để mở rộng · thu gọn để xem nhiều bộ lọc cùng lúc</small>
    </div>
    <button class="btn btn-outline-primary btn-sm" id="btnAdd">
        <i class="bi bi-plus-circle me-1"></i>Thêm bộ lọc
    </button>
</div>

<div id="cmpContainer" class="row g-2"></div>

<div class="text-center mt-3 mb-4">
    <button class="btn btn-outline-secondary btn-sm" id="btnAddBottom">
        <i class="bi bi-plus-lg me-1"></i>Thêm bộ lọc so sánh
    </button>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>
<script>
const SHOPS     = @json($shops->map(fn($s) => ['id' => $s->id, 'name' => $s->name]));
const BY_SHOP   = @json($productsByShop);
const STATS_URL = '{{ route("reports.compare-stats") }}';
const CSRF      = document.querySelector('meta[name="csrf-token"]').content;
const D_FROM    = '{{ now()->startOfMonth()->format("Y-m-d") }}';
const D_TO      = '{{ now()->format("Y-m-d") }}';
const COLORS    = ['#3b82f6','#ef4444','#10b981','#f59e0b','#8b5cf6','#ec4899','#06b6d4','#84cc16'];

let cnt = 0;

// ─── Abbreviate number ────────────────────────────────────────────────────────
function abbr(v) {
    const a = Math.abs(v);
    const s = v < 0 ? '-' : '';
    if (a >= 1e9) return s + (a/1e9).toFixed(1) + 'T';
    if (a >= 1e6) return s + (a/1e6).toFixed(1) + 'M';
    if (a >= 1e3) return s + (a/1e3).toFixed(0) + 'K';
    return s + Math.round(a);
}
function fmt(v) { return Math.round(v).toLocaleString('vi-VN') + '₫'; }
function fmtDate(d) { return d ? d.split('-').slice(1).reverse().join('/') : ''; }

// ─── Build card HTML ─────────────────────────────────────────────────────────
function buildCard(n) {
    const color    = COLORS[(n - 1) % COLORS.length];
    const shopOpts = SHOPS.map(s => `<option value="${s.id}">${s.name}</option>`).join('');

    return `
<div class="cmp-card is-open" data-item-id="${n}">

    <div class="cmp-header" data-toggle>
        <i class="bi bi-chevron-right cmp-toggle-icon"></i>
        <span class="cmp-tag" style="background:${color}">#${n}</span>
        <span class="cmp-summary">— Chưa chọn shop —</span>
        <span class="cmp-metrics d-none">
            <span>DS <span class="m-ds metric-ds"></span></span>
            <span class="text-muted">·</span>
            <span>LN <span class="m-ln metric-ln"></span></span>
        </span>
        <button type="button" class="btn btn-link p-0 ms-1 btn-remove text-danger"
                style="font-size:.75rem;line-height:1" title="Xoá" data-stop>
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="cmp-body">
        <div class="cmp-inner">

            <div class="row g-1 mb-1">
                <div class="col">
                    <select class="form-select form-select-sm item-shop">
                        <option value="">— Chọn shop —</option>
                        ${shopOpts}
                    </select>
                </div>
                <div class="col-auto">
                    <input type="text" class="form-control form-control-sm item-from"
                           style="width:110px" placeholder="dd/mm/yyyy" readonly>
                </div>
                <div class="col-auto">
                    <input type="text" class="form-control form-control-sm item-to"
                           style="width:110px" placeholder="dd/mm/yyyy" readonly>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-primary btn-sm btn-fetch h-100 px-2">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>

            <div class="mb-2">
                <select class="form-select form-select-sm item-prods" multiple>
                </select>
            </div>

            <div class="item-loading d-none text-center py-2">
                <div class="spinner-border text-primary"
                     style="width:.8rem;height:.8rem;border-width:.14em"></div>
                <span class="ms-1 text-muted" style="font-size:.74rem">Đang tải...</span>
            </div>

            <div class="item-empty text-center text-muted py-2" style="font-size:.74rem">
                <i class="bi bi-bar-chart opacity-25 me-1"></i>Chọn shop và nhấn <strong>Xem</strong>
            </div>

            <div class="item-stats d-none">

                <div class="stats-row d-flex flex-wrap gap-3">
                    <div>
                        <span class="lbl">DOANH SỐ</span>
                        <span class="val text-primary stat-revenue">—</span>
                    </div>
                    <div>
                        <span class="lbl">GIÁ VỐN</span>
                        <span class="val stat-cost" style="color:#7c3aed">—</span>
                    </div>
                    <div>
                        <span class="lbl">SỐ LƯỢNG</span>
                        <span class="val stat-qty" style="color:#d97706">—</span>
                    </div>
                </div>

                <details class="mt-2">
                    <summary class="d-flex align-items-center gap-1 py-1"
                             style="font-size:.72rem;color:#64748b">
                        <i class="bi bi-chevron-right det-ico" style="font-size:.6rem;transition:transform .15s"></i>
                        Chi tiết sản phẩm
                        <span class="badge bg-light text-muted border ms-1 prod-count" style="font-size:.6rem"></span>
                    </summary>
                    <table class="table pd-tbl table-hover mb-1 mt-1">
                        <thead class="table-light">
                            <tr>
                                <th>Sản phẩm</th>
                                <th class="r">SL</th>
                                <th class="r">Doanh số</th>
                                <th class="r">Giá vốn</th>
                                <th class="r">LN *</th>
                                <th class="r">%LN</th>
                            </tr>
                        </thead>
                        <tbody class="prod-rows"></tbody>
                    </table>
                    <small class="text-muted d-block" style="font-size:.65rem">
                        * Chưa trừ phí vận hành, ADS, KOL
                    </small>
                </details>
            </div>

        </div>
    </div>
</div>`;
}

// ─── Bind events ─────────────────────────────────────────────────────────────
function bind(card) {
    const shopSel  = card.querySelector('.item-shop');
    const prodSel  = card.querySelector('.item-prods');
    const fetchBtn = card.querySelector('.btn-fetch');
    const removeBtn= card.querySelector('.btn-remove');
    const header   = card.querySelector('[data-toggle]');

    // ── Flatpickr cho date inputs ──────────────────────────────────────────────
    const fpOpts = {
        dateFormat : 'Y-m-d',
        altInput   : true,
        altFormat  : 'd/m/Y',
        allowInput : true,
        locale     : 'vn',
        onChange   : () => updateSummary(card),
    };
    card._fpFrom = flatpickr(card.querySelector('.item-from'), { ...fpOpts, defaultDate: D_FROM });
    card._fpTo   = flatpickr(card.querySelector('.item-to'),   { ...fpOpts, defaultDate: D_TO   });

    // ── Tom Select khởi tạo ban đầu (placeholder, disable) ────────────────────
    card._ts = new TomSelect(prodSel, {
        plugins       : { remove_button: {} },
        placeholder   : 'Chọn shop trước...',
        maxOptions    : null,
        dropdownParent: 'body',
        render: {
            no_results: () => '<div class="px-3 py-2 text-muted" style="font-size:.75rem">Không tìm thấy</div>',
        },
    });
    card._ts.lock(); // khóa cho đến khi chọn shop

    // Toggle collapse/expand
    header.addEventListener('click', function (e) {
        if (e.target.closest('[data-stop]')) return;
        card.classList.toggle('is-open');
    });

    // Shop change → load products
    shopSel.addEventListener('change', function () {
        // Xoá Tom Select cũ, tạo lại
        card._ts.destroy();
        card._ts = null;

        if (!this.value) {
            prodSel.innerHTML = '';
            card._ts = new TomSelect(prodSel, {
                plugins       : { remove_button: {} },
                placeholder   : 'Chọn shop trước...',
                maxOptions    : null,
                dropdownParent: 'body',
            });
            card._ts.lock();
            updateSummary(card);
            return;
        }

        const prods = BY_SHOP[this.value] || [];
        prodSel.innerHTML = '';
        prods.forEach(p => prodSel.appendChild(new Option(p.name, p.id)));

        card._ts = new TomSelect(prodSel, {
            plugins       : { remove_button: {} },
            placeholder   : prods.length ? 'Tất cả (để trống = lấy hết)...' : 'Không có sản phẩm',
            maxOptions    : null,
            dropdownParent: 'body',
            render: {
                no_results: () => '<div class="px-3 py-2 text-muted" style="font-size:.75rem">Không tìm thấy</div>',
            },
        });
        if (!prods.length) card._ts.lock();

        updateSummary(card);
        resetStats(card);
    });

    fetchBtn.addEventListener('click', () => fetchStats(card));

    // Enter trên altInput của flatpickr
    [shopSel, card._fpFrom.altInput, card._fpTo.altInput]
        .forEach(el => el && el.addEventListener('keydown', e => { if (e.key === 'Enter') fetchStats(card); }));

    // Details icon rotate
    const det = card.querySelector('details');
    if (det) det.addEventListener('toggle', () => {
        const ico = det.querySelector('summary .det-ico');
        if (ico) ico.style.transform = det.open ? 'rotate(90deg)' : '';
    });

    // Remove
    removeBtn.addEventListener('click', e => {
        e.stopPropagation();
        if (document.querySelectorAll('.cmp-card').length <= 1) return;
        if (card._ts) card._ts.destroy();
        if (card._fpFrom) card._fpFrom.destroy();
        if (card._fpTo)   card._fpTo.destroy();
        (card.closest('.col-cmp') ?? card).remove();
    });
}

// ─── Update header summary text ───────────────────────────────────────────────
function updateSummary(card) {
    const shopSel  = card.querySelector('.item-shop');
    const shopName = shopSel.options[shopSel.selectedIndex]?.text ?? '—';
    const from     = fmtDate(card.querySelector('.item-from').value);
    const to       = fmtDate(card.querySelector('.item-to').value);
    if (shopSel.value) {
        card.querySelector('.cmp-summary').textContent = `${shopName} · ${from} → ${to}`;
    } else {
        card.querySelector('.cmp-summary').textContent = '— Chưa chọn shop —';
    }
}

// ─── Reset stats ──────────────────────────────────────────────────────────────
function resetStats(card) {
    card.querySelector('.item-stats').classList.add('d-none');
    card.querySelector('.item-empty').classList.remove('d-none');
    card.querySelector('.cmp-metrics').classList.add('d-none');
}

// ─── Fetch AJAX ───────────────────────────────────────────────────────────────
async function fetchStats(card) {
    const shopId = card.querySelector('.item-shop').value;
    const from   = card.querySelector('.item-from').value;
    const to     = card.querySelector('.item-to').value;
    const selIds = card._ts ? card._ts.getValue().filter(Boolean) : [];

    if (!shopId) {
        const s = card.querySelector('.item-shop');
        s.classList.add('is-invalid');
        setTimeout(() => s.classList.remove('is-invalid'), 1500);
        return;
    }

    const loadEl  = card.querySelector('.item-loading');
    const statsEl = card.querySelector('.item-stats');
    const emptyEl = card.querySelector('.item-empty');

    emptyEl.classList.add('d-none');
    statsEl.classList.add('d-none');
    loadEl.classList.remove('d-none');

    const p = new URLSearchParams({ shop_id: shopId, date_from: from, date_to: to });
    selIds.forEach(id => p.append('product_ids[]', id));

    try {
        const resp = await fetch(`${STATS_URL}?${p}`, {
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF }
        });
        if (!resp.ok) throw new Error('HTTP ' + resp.status);
        const d = await resp.json();

        // Summary bar
        card.querySelector('.stat-revenue').textContent = fmt(d.total_revenue);
        card.querySelector('.stat-cost').textContent    = fmt(d.total_cost);
        card.querySelector('.stat-qty').textContent     = (+d.total_qty).toLocaleString('vi-VN');

        // Header metrics (visible when collapsed)
        card.querySelector('.metric-ds').textContent = abbr(d.total_revenue) + '₫';
        const lnEl = card.querySelector('.metric-ln');
        lnEl.textContent = abbr(d.total_profit) + '₫';
        lnEl.className   = `m-ln ${d.total_profit >= 0 ? 'pos' : 'neg'}`;
        card.querySelector('.cmp-metrics').classList.remove('d-none');

        // Product rows
        const tbody = card.querySelector('.prod-rows');
        const prods = d.products || [];
        card.querySelector('.prod-count').textContent = prods.length || '';
        tbody.innerHTML = '';

        if (prods.length) {
            prods.forEach(p => {
                const pct = p.total_revenue > 0
                    ? (p.total_profit / p.total_revenue * 100).toFixed(1) : '0.0';
                const pc2 = p.total_profit >= 0 ? 'pp' : 'pn';
                const tr  = document.createElement('tr');
                tr.innerHTML = `
                    <td class="fw-semibold">${p.product_name}</td>
                    <td class="r">${(+p.total_qty).toLocaleString('vi-VN')}</td>
                    <td class="r">${Math.round(p.total_revenue).toLocaleString('vi-VN')}₫</td>
                    <td class="r">${Math.round(p.total_cost).toLocaleString('vi-VN')}₫</td>
                    <td class="r ${pc2}">${Math.round(p.total_profit).toLocaleString('vi-VN')}₫</td>
                    <td class="r ${p.total_profit >= 0 ? 'text-success' : 'text-danger'}">${pct}%</td>`;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-2">Không có dữ liệu</td></tr>';
        }

        statsEl.classList.remove('d-none');
    } catch {
        emptyEl.innerHTML = '<i class="bi bi-exclamation-circle text-danger me-1"></i><span class="text-danger">Lỗi tải. Thử lại.</span>';
        emptyEl.classList.remove('d-none');
    } finally {
        loadEl.classList.add('d-none');
    }
}

// ─── Add item ─────────────────────────────────────────────────────────────────
function addItem() {
    cnt++;
    const col = document.createElement('div');
    col.className = 'col-12 col-lg-6 col-cmp';
    col.innerHTML = buildCard(cnt);
    document.getElementById('cmpContainer').appendChild(col);
    bind(col.querySelector('.cmp-card'));
    col.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

document.getElementById('btnAdd').addEventListener('click', addItem);
document.getElementById('btnAddBottom').addEventListener('click', addItem);

// Init
addItem();
</script>
@endpush
