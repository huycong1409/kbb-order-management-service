<tr>
    @if(!empty($variant['id']))
        <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant['id'] }}">
    @endif
    <td>
        <input type="text" name="variants[{{ $index }}][name]"
               class="form-control form-control-sm"
               value="{{ $variant['name'] ?? '' }}" placeholder="VD: 20cm" required>
    </td>
    <td>
        <input type="text" name="variants[{{ $index }}][sku]"
               class="form-control form-control-sm"
               value="{{ $variant['sku'] ?? '' }}" placeholder="SKU">
    </td>
    <td>
        <input type="number" name="variants[{{ $index }}][cost_price]"
               class="form-control form-control-sm text-end"
               value="{{ $variant['cost_price'] ?? 0 }}" min="0" required>
    </td>
    <td>
        @if(!empty($variant['id']))
            {{-- Xoá variant đã có trong DB --}}
            <a href="{{ route('shops.products.variants.destroy', [$shopId ?? 0, $productId ?? 0, $variant['id']]) }}"
               class="btn btn-sm btn-outline-danger"
               onclick="return confirm('Xoá phân loại {{ addslashes($variant['name'] ?? '') }}?')">
                <i class="bi bi-x"></i>
            </a>
        @else
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-variant">
                <i class="bi bi-x"></i>
            </button>
        @endif
    </td>
</tr>
