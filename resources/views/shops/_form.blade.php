<div class="mb-3">
    <label class="form-label fw-semibold">Tên Shop <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $shop->name ?? '') }}" placeholder="VD: KingBamboo Shopee">
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Platform <span class="text-danger">*</span></label>
    <select name="platform" class="form-select @error('platform') is-invalid @enderror">
        @foreach(['shopee' => 'Shopee', 'lazada' => 'Lazada', 'tiki' => 'Tiki', 'sendo' => 'Sendo', 'other' => 'Khác'] as $val => $label)
            <option value="{{ $val }}" {{ old('platform', $shop->platform ?? 'shopee') === $val ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('platform') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">URL Shop</label>
    <input type="url" name="url" class="form-control @error('url') is-invalid @enderror"
           value="{{ old('url', $shop->url ?? '') }}" placeholder="https://shopee.vn/shop/...">
    @error('url') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Mô tả</label>
    <textarea name="description" class="form-control @error('description') is-invalid @enderror"
              rows="3" placeholder="Ghi chú về shop...">{{ old('description', $shop->description ?? '') }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="form-check form-switch">
    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
           {{ old('is_active', $shop->is_active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="isActive">Đang hoạt động</label>
</div>
