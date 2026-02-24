@extends('layouts.app')
@section('title', 'Import Excel Đơn hàng')
@section('breadcrumb', 'Import Excel')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-bold">Import Đơn hàng từ Excel</h5>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header fw-semibold">
                <i class="bi bi-file-earmark-excel text-success me-1"></i> Upload file Shopee
            </div>
            <div class="card-body">
                <form action="{{ route('orders.import') }}" method="POST" enctype="multipart/form-data"
                      id="importForm">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Shop <span class="text-danger">*</span></label>
                        <select name="shop_id" class="form-select @error('shop_id') is-invalid @enderror">
                            <option value="">-- Chọn Shop --</option>
                            @foreach($shops as $shop)
                                <option value="{{ $shop->id }}"
                                    {{ old('shop_id') == $shop->id ? 'selected' : '' }}>
                                    {{ $shop->name }}
                                    <span class="text-muted">({{ strtoupper($shop->platform) }})</span>
                                </option>
                            @endforeach
                        </select>
                        @error('shop_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">File Excel <span class="text-danger">*</span></label>
                        <div class="upload-area border border-2 border-dashed rounded p-4 text-center"
                             id="dropZone"
                             style="border-color: #cbd5e1 !important; cursor: pointer; transition: .2s">
                            <i class="bi bi-file-earmark-excel fs-2 text-success mb-2 d-block"></i>
                            <p class="mb-1 fw-semibold">Kéo thả file vào đây hoặc</p>
                            <label class="btn btn-sm btn-outline-success mb-1" for="fileInput">
                                Chọn file
                            </label>
                            <input type="file" name="file" id="fileInput" accept=".xlsx,.xls"
                                   class="d-none @error('file') is-invalid @enderror">
                            <p class="text-muted mb-0" style="font-size:.75rem" id="fileLabel">
                                .xlsx, .xls — Tối đa 20MB
                            </p>
                        </div>
                        @error('file')
                            <div class="text-danger mt-1" style="font-size:.875rem">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-success w-100" id="btnImport">
                        <i class="bi bi-upload me-1"></i> Bắt đầu Import
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header fw-semibold">
                <i class="bi bi-info-circle me-1"></i> Hướng dẫn
            </div>
            <div class="card-body">
                <ol class="mb-0" style="font-size:.85rem; line-height:1.8">
                    <li>Chọn <strong>Shop</strong> tương ứng với file cần import</li>
                    <li>Upload file <strong>Excel xuất từ Shopee</strong> (Đơn hàng → Xuất)</li>
                    <li>File phải có sheet tên <code>orders</code></li>
                    <li>Chỉ import đơn có trạng thái <span class="badge bg-success">Hoàn thành</span></li>
                    <li>Đơn hàng trùng <strong>Mã đơn</strong> sẽ được cập nhật (không tạo mới)</li>
                    <li><strong>Giá vốn</strong> sẽ tự động lấy từ danh mục sản phẩm theo tên</li>
                </ol>
                <hr>
                <div class="alert alert-warning mb-0 py-2" style="font-size:.8rem">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>Lưu ý:</strong> Đảm bảo đã nhập đủ sản phẩm &amp; phân loại trước khi import để
                    giá vốn được điền đúng.
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header fw-semibold">Cột mapping từ file Shopee</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:.75rem">
                    <thead><tr><th>Cột file Shopee</th><th>Trường hệ thống</th></tr></thead>
                    <tbody>
                        @foreach([
                            ['Mã đơn hàng', 'order_code'],
                            ['Ngày đặt hàng', 'order_date'],
                            ['Trạng thái đơn hàng', 'status (Hoàn thành)'],
                            ['Tên sản phẩm', 'product_name'],
                            ['Tên phân loại hàng', 'variant_name'],
                            ['Số lượng', 'quantity'],
                            ['Tổng giá bán (sản phẩm)', 'selling_price'],
                            ['Phí vận chuyển (dự kiến)', 'pi_ship'],
                            ['Phí cố định', 'fixed_fee'],
                            ['Phí Dịch Vụ', 'service_fee'],
                            ['Phí thanh toán', 'payment_fee'],
                        ] as [$src, $dst])
                        <tr>
                            <td class="text-muted">{{ $src }}</td>
                            <td><code>{{ $dst }}</code></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const input    = document.getElementById('fileInput');
const label    = document.getElementById('fileLabel');
const dropZone = document.getElementById('dropZone');
const btnImport = document.getElementById('btnImport');

input.addEventListener('change', function () {
    if (this.files[0]) {
        label.textContent = this.files[0].name + ' (' + (this.files[0].size / 1024 / 1024).toFixed(2) + ' MB)';
        dropZone.style.borderColor = '#22c55e';
        dropZone.style.background  = '#f0fdf4';
    }
});

dropZone.addEventListener('click', () => input.click());
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor = '#3b82f6'; });
dropZone.addEventListener('dragleave', () => { dropZone.style.borderColor = '#cbd5e1'; });
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    input.files = e.dataTransfer.files;
    input.dispatchEvent(new Event('change'));
});

document.getElementById('importForm').addEventListener('submit', function () {
    btnImport.disabled    = true;
    btnImport.innerHTML   = '<span class="spinner-border spinner-border-sm me-1"></span> Đang xử lý...';
});
</script>
@endpush
