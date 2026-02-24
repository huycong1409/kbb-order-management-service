<div class="mb-3">
    <label class="form-label fw-semibold">Tên <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $user->name ?? '') }}" placeholder="Nguyễn Văn A">
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
           value="{{ old('email', $user->email ?? '') }}" placeholder="email@example.com">
    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">
        Mật khẩu {{ isset($user) ? '(để trống nếu không đổi)' : '' }}
        @if(!isset($user)) <span class="text-danger">*</span> @endif
    </label>
    <div style="position:relative">
        <input type="password" name="password" id="pw"
               class="form-control @error('password') is-invalid @enderror"
               placeholder="Tối thiểu 8 ký tự"
               autocomplete="new-password">
        <button type="button" onclick="togglePw('pw','eye1')"
                style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer">
            <i class="bi bi-eye" id="eye1"></i>
        </button>
    </div>
    @error('password') <div class="text-danger mt-1" style="font-size:.875rem">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">
        Xác nhận mật khẩu {{ isset($user) ? '(để trống nếu không đổi)' : '' }}
        @if(!isset($user)) <span class="text-danger">*</span> @endif
    </label>
    <div style="position:relative">
        <input type="password" name="password_confirmation" id="pw2"
               class="form-control"
               placeholder="Nhập lại mật khẩu"
               autocomplete="new-password">
        <button type="button" onclick="togglePw('pw2','eye2')"
                style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer">
            <i class="bi bi-eye" id="eye2"></i>
        </button>
    </div>
</div>

@push('scripts')
<script>
function togglePw(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>
@endpush
