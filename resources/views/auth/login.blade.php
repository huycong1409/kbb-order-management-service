<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập — KBB Order Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        /* ── Page ───────────────────────────────────────────────────────── */
        html, body {
            height: 100%; margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: #09111f;
            color: #f1f5f9;
            -webkit-font-smoothing: antialiased;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }

        /* Background orbs — subtle depth */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            opacity: .45;
            animation: orbFloat 12s ease-in-out infinite;
        }
        .orb-1 {
            width: 480px; height: 480px;
            background: radial-gradient(circle, #0c4a6e 0%, transparent 70%);
            top: -120px; left: -100px;
            animation-delay: 0s;
        }
        .orb-2 {
            width: 360px; height: 360px;
            background: radial-gradient(circle, #1e1b4b 0%, transparent 70%);
            bottom: -80px; right: -80px;
            animation-delay: 6s;
        }
        .orb-3 {
            width: 260px; height: 260px;
            background: radial-gradient(circle, #083344 0%, transparent 70%);
            top: 40%; right: 20%;
            animation-delay: 3s;
        }
        @keyframes orbFloat {
            0%, 100% { transform: translate(0, 0); }
            33%       { transform: translate(18px, -14px); }
            66%       { transform: translate(-12px, 10px); }
        }

        /* ── Wrapper ────────────────────────────────────────────────────── */
        .login-wrapper {
            position: relative; z-index: 1;
            width: 100%; max-width: 400px;
            padding: 1rem;
        }

        /* ── Brand mark (above card) ────────────────────────────────────── */
        @keyframes brandIn {
            from { opacity: 0; transform: translateY(-12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .brand-mark {
            text-align: center;
            margin-bottom: 1.75rem;
            animation: brandIn .5s ease both;
        }
        .brand-mark .icon-box {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            border-radius: 12px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.4rem; color: #fff;
            box-shadow: 0 4px 16px rgba(14,165,233,.35);
            margin-bottom: .75rem;
        }
        .brand-mark h1 {
            font-size: 1.05rem; font-weight: 700;
            color: #f1f5f9; letter-spacing: -.01em; margin: 0;
        }
        .brand-mark p {
            font-size: .78rem; color: #64748b; margin: .2rem 0 0;
        }

        /* ── Card ───────────────────────────────────────────────────────── */
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .login-card {
            background: rgba(15, 23, 42, .9);
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 14px;
            padding: 2rem;
            box-shadow:
                0 0 0 1px rgba(255,255,255,.03),
                0 20px 60px rgba(0,0,0,.5);
            animation: cardIn .5s ease .1s both;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        /* ── Form typography ────────────────────────────────────────────── */
        .form-section-title {
            font-size: .95rem; font-weight: 600;
            color: #e2e8f0; margin-bottom: 1.5rem;
            text-align: center; letter-spacing: -.01em;
        }
        .form-section-title span {
            color: #94a3b8; font-weight: 400; font-size: .8rem;
            display: block; margin-top: .25rem;
        }

        .form-label {
            font-size: .75rem; font-weight: 600;
            color: #94a3b8; letter-spacing: .05em;
            text-transform: uppercase; margin-bottom: .4rem;
        }

        /* ── Inputs ─────────────────────────────────────────────────────── */
        .field-wrap { position: relative; }
        .field-wrap .field-icon {
            position: absolute; left: .85rem; top: 50%;
            transform: translateY(-50%);
            color: #334155; font-size: .85rem;
            pointer-events: none;
            transition: color .18s;
        }
        .field-wrap:focus-within .field-icon { color: #0ea5e9; }

        .form-control {
            width: 100%;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 8px;
            color: #f1f5f9;
            font-size: .875rem;
            padding: .65rem .85rem .65rem 2.4rem;
            transition: border-color .18s, box-shadow .18s, background .18s;
            outline: none;
        }
        .form-control::placeholder { color: #334155; }
        .form-control:focus {
            border-color: rgba(14,165,233,.55);
            background: rgba(14,165,233,.04);
            box-shadow: 0 0 0 3px rgba(14,165,233,.1);
            color: #f1f5f9;
        }
        .form-control.is-invalid {
            border-color: rgba(239,68,68,.5);
        }
        .form-control.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(239,68,68,.12);
        }

        /* toggle password */
        .toggle-pw {
            position: absolute; right: .75rem; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: #334155; font-size: .85rem;
            cursor: pointer; padding: 0;
            transition: color .15s;
        }
        .toggle-pw:hover { color: #94a3b8; }

        /* ── Remember me ────────────────────────────────────────────────── */
        .form-check-input {
            width: 15px; height: 15px;
            background-color: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 4px;
            cursor: pointer;
            flex-shrink: 0;
        }
        .form-check-input:checked {
            background-color: #0ea5e9;
            border-color: #0ea5e9;
        }
        .form-check-label {
            font-size: .8rem; color: #64748b;
            cursor: pointer;
        }

        /* ── Alert ──────────────────────────────────────────────────────── */
        @keyframes alertIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .alert-error {
            background: rgba(239,68,68,.08);
            border: 1px solid rgba(239,68,68,.2);
            border-radius: 8px;
            color: #fca5a5;
            font-size: .8rem;
            padding: .65rem .85rem;
            margin-bottom: 1.25rem;
            display: flex; align-items: center; gap: .5rem;
            animation: alertIn .25s ease both;
        }

        /* ── Submit button ──────────────────────────────────────────────── */
        .btn-submit {
            width: 100%; padding: .7rem;
            background: #0ea5e9;
            border: none; border-radius: 8px;
            color: #fff; font-size: .875rem; font-weight: 600;
            cursor: pointer; letter-spacing: .01em;
            position: relative; overflow: hidden;
            transition: background .18s, transform .12s, box-shadow .18s;
        }
        .btn-submit:hover {
            background: #0284c7;
            box-shadow: 0 4px 18px rgba(14,165,233,.3);
            transform: translateY(-1px);
        }
        .btn-submit:active { transform: scale(.98); }
        .btn-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }

        /* ── Divider ────────────────────────────────────────────────────── */
        .card-divider {
            border: none;
            border-top: 1px solid rgba(255,255,255,.06);
            margin: 1.5rem 0;
        }

        /* ── Footer ─────────────────────────────────────────────────────── */
        @keyframes footerIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        .login-footer {
            text-align: center; margin-top: 1.5rem;
            font-size: .72rem; color: #334155;
            animation: footerIn .5s ease .4s both;
        }

        /* ── Spin ───────────────────────────────────────────────────────── */
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner {
            display: inline-block;
            width: 13px; height: 13px;
            border: 2px solid rgba(255,255,255,.25);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .6s linear infinite;
            vertical-align: middle;
            margin-right: 6px;
        }

        /* ── Stagger fields ─────────────────────────────────────────────── */
        @keyframes fieldIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .f1 { animation: fieldIn .3s ease .2s both; }
        .f2 { animation: fieldIn .3s ease .28s both; }
        .f3 { animation: fieldIn .3s ease .36s both; }
        .f4 { animation: fieldIn .3s ease .44s both; }
    </style>
</head>
<body>

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="login-wrapper">

    {{-- Brand mark --}}
    <div class="brand-mark">
        <div class="icon-box"><i class="bi bi-box-seam"></i></div>
        <h1>KBB Order Management</h1>
        <p>Quản lý đơn hàng KingBamboo</p>
    </div>

    {{-- Card --}}
    <div class="login-card">

        <div class="form-section-title">
            Đăng nhập
            <span>Vui lòng nhập thông tin tài khoản của bạn</span>
        </div>

        @if($errors->any())
        <div class="alert-error">
            <i class="bi bi-exclamation-circle-fill" style="flex-shrink:0"></i>
            {{ $errors->first() }}
        </div>
        @endif

        <form action="{{ route('login') }}" method="POST" id="loginForm">
            @csrf

            <div class="mb-3 f1">
                <label class="form-label">Email</label>
                <div class="field-wrap">
                    <i class="bi bi-envelope field-icon"></i>
                    <input type="email" name="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}"
                           placeholder="you@example.com"
                           autofocus autocomplete="email">
                </div>
            </div>

            <div class="mb-3 f2">
                <label class="form-label">Mật khẩu</label>
                <div class="field-wrap">
                    <i class="bi bi-lock field-icon"></i>
                    <input type="password" name="password" id="passwordInput"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="••••••••"
                           autocomplete="current-password"
                           style="padding-right:2.4rem">
                    <button type="button" class="toggle-pw" onclick="togglePw()" tabindex="-1">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <hr class="card-divider f3">

            <div class="d-flex align-items-center justify-content-between mb-4 f3">
                <div class="form-check d-flex align-items-center gap-2 m-0">
                    <input class="form-check-input mt-0" type="checkbox"
                           name="remember" id="remember">
                    <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                </div>
            </div>

            <div class="f4">
                <button type="submit" class="btn-submit" id="btnSubmit">
                    <span id="btnContent">Đăng nhập</span>
                </button>
            </div>
        </form>
    </div>

    <div class="login-footer">
        KingBamboo &copy; {{ date('Y') }} &nbsp;·&nbsp; v1.0
    </div>
</div>

<script>
function togglePw() {
    const input = document.getElementById('passwordInput');
    const icon  = document.getElementById('eyeIcon');
    const show  = input.type === 'password';
    input.type     = show ? 'text' : 'password';
    icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
}

document.getElementById('loginForm').addEventListener('submit', function () {
    const btn = document.getElementById('btnSubmit');
    const con = document.getElementById('btnContent');
    btn.disabled = true;
    con.innerHTML = '<span class="spinner"></span>Đang xác thực...';
});
</script>
</body>
</html>
