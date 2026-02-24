<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'KBB Order Management')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @vite(['resources/css/app.css'])
    @stack('styles')
</head>
<body>

{{-- Sidebar --}}
<nav class="sidebar d-flex flex-column">
    <div class="sidebar-brand">
        <h6><i class="bi bi-box-seam"></i> KBB Orders</h6>
        <p>Order Management</p>
    </div>

    <div class="mt-2">
        <div class="nav-section">Tổng quan</div>
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="{{ route('shops.index') }}" class="nav-link {{ request()->routeIs('shops.*') ? 'active' : '' }}">
            <i class="bi bi-shop"></i> Quản lý Shop
        </a>
        <a href="{{ route('orders.index') }}" class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
            <i class="bi bi-receipt"></i> Đơn hàng
        </a>
        <a href="{{ route('reports.monthly') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <i class="bi bi-bar-chart-line"></i> Báo cáo
        </a>

        <div class="nav-section mt-2">Import</div>
        <a href="{{ route('orders.import-form') }}" class="nav-link {{ request()->routeIs('orders.import*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-excel"></i> Import Excel
        </a>

        <div class="nav-section mt-2">Hệ thống</div>
        <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
            <i class="bi bi-people"></i> Tài khoản
        </a>
    </div>

    {{-- User info + logout ở cuối sidebar --}}
    <div class="mt-auto" style="border-top:1px solid #2d3f55; padding: .875rem .75rem">
        <div class="d-flex align-items-center gap-2 px-1 mb-2">
            <div style="width:34px;height:34px;border-radius:50%;background:#38bdf8;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="bi bi-person-fill" style="font-size:.95rem;color:#0f172a"></i>
            </div>
            <div style="min-width:0">
                <div style="font-size:.8rem;font-weight:700;color:#f1f5f9;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    {{ Auth::user()->name }}
                </div>
                <div style="font-size:.7rem;color:#94a3b8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    {{ Auth::user()->email }}
                </div>
            </div>
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="nav-link w-100 text-start border-0 bg-transparent"
                    style="color:#f87171;font-size:.8rem;font-weight:500">
                <i class="bi bi-box-arrow-right"></i> Đăng xuất
            </button>
        </form>
    </div>
</nav>

{{-- Main --}}
<div class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between">
        <div>
            <span class="text-muted fw-semibold" style="font-size:0.8rem">@yield('breadcrumb', 'Dashboard')</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted" style="font-size:0.75rem">{{ now()->format('d/m/Y') }}</span>
        </div>
    </div>

    <div class="page-wrapper">
        {{-- Flash messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                {{ session('success') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible d-flex align-items-center gap-2 mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                {{ session('warning') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3" role="alert">
                <i class="bi bi-x-circle-fill"></i>
                {{ session('error') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('import_errors'))
            <div class="alert alert-warning mb-3">
                <strong>Chi tiết lỗi import:</strong>
                <ul class="mb-0 mt-1">
                    @foreach(session('import_errors') as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Helper: format số theo kiểu VN
function formatVND(num) {
    if (num === null || num === undefined || isNaN(num)) return '0';
    return Math.round(num).toLocaleString('vi-VN');
}

// ─── Count-up animation cho stat-card .value ────────────────────────────────
function countUp(el, to, duration = 800) {
    const raw     = to.replace(/[^\d.-]/g, '');
    const target  = parseFloat(raw);
    const suffix  = to.replace(/[\d,.\s-]/g, '').trim(); // ₫ hoặc rỗng
    if (isNaN(target)) return;
    const start  = performance.now();
    const from   = 0;
    function step(now) {
        const progress = Math.min((now - start) / duration, 1);
        const ease     = 1 - Math.pow(1 - progress, 3); // ease-out cubic
        const current  = from + (target - from) * ease;
        el.textContent = Math.round(current).toLocaleString('vi-VN') + (suffix ? suffix : '');
        if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

document.addEventListener('DOMContentLoaded', function () {
    // Count-up cho tất cả stat-card .value
    document.querySelectorAll('.stat-card .value').forEach(el => {
        const original = el.textContent.trim();
        el.textContent = '0';
        setTimeout(() => countUp(el, original, 700), 100);
    });

    // Auto loading state khi submit form (trừ form logout/delete)
    document.querySelectorAll('form:not([data-no-loading])').forEach(form => {
        form.addEventListener('submit', function (e) {
            const btn = form.querySelector('button[type="submit"]');
            if (btn && !btn.classList.contains('btn-outline-danger') && !btn.dataset.noLoading) {
                setTimeout(() => btn.classList.add('btn-loading'), 50);
            }
        });
    });

    // Stagger animation cho các card trong .row
    document.querySelectorAll('.row .card').forEach((card, i) => {
        card.style.animationDelay = (i * 0.06) + 's';
    });

    // Stagger animation cho table rows
    document.querySelectorAll('.table tbody tr').forEach((tr, i) => {
        tr.style.opacity = '0';
        tr.style.animation = `fadeInUp .2s ease ${(i * 0.02).toFixed(2)}s both`;
    });
});
</script>
@stack('scripts')
</body>
</html>
