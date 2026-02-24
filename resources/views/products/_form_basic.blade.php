<div class="mb-3">
    <label class="form-label fw-semibold">Tên Sản phẩm <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $product->name ?? '') }}"
           placeholder="VD: Lót Ly, Lót Nồi Bằng Mây Tre Đan KINGBAMBOO...">
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label fw-semibold">SKU</label>
        <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror"
               value="{{ old('sku', $product->sku ?? '') }}" placeholder="Mã SKU sản phẩm">
        @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label fw-semibold">Giá vốn mặc định (₫) <span class="text-danger">*</span></label>
        <input type="number" name="cost_price" class="form-control @error('cost_price') is-invalid @enderror"
               value="{{ old('cost_price', $product->cost_price ?? 0) }}"
               placeholder="0" min="0">
        <div class="form-text">Dùng khi phân loại chưa có giá vốn riêng</div>
        @error('cost_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Mô tả</label>
    <textarea name="description" class="form-control @error('description') is-invalid @enderror"
              rows="2" placeholder="Mô tả sản phẩm...">{{ old('description', $product->description ?? '') }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="form-check form-switch">
    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
           {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="isActive">Đang bán</label>
</div>
