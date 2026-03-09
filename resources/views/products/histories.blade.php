@extends('layouts.app')
@section('title', 'Lịch sử: ' . Str::limit($product->name, 40))
@section('breadcrumb', 'Sản phẩm / Lịch sử')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('products.all', ['shop_id' => $product->shop_id]) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h5 class="mb-0 fw-bold">Lịch sử phiên bản</h5>
        <small class="text-muted">{{ $product->name }}</small>
    </div>
    <span class="badge bg-danger-subtle text-danger ms-1">{{ $product->shop->name ?? '—' }}</span>
</div>

{{-- Phiên bản hiện tại --}}
<div class="card mb-3 border-primary">
    <div class="card-header d-flex justify-content-between align-items-center bg-primary-subtle">
        <span class="fw-semibold text-primary">
            <i class="bi bi-check-circle-fill me-1"></i> Phiên bản hiện tại
        </span>
        <a href="{{ route('shops.products.edit', [$product->shop_id, $product->id]) }}?_back=products.all"
           class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil"></i> Sửa
        </a>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="mb-2">
                    <span class="text-muted small">Tên sản phẩm</span>
                    <div class="fw-semibold">{{ $product->name }}</div>
                </div>
                <div class="mb-2">
                    <span class="text-muted small">SKU</span>
                    <div><code>{{ $product->sku ?? '—' }}</code></div>
                </div>
                <div>
                    <span class="text-muted small">Giá vốn mặc định</span>
                    <div class="fw-semibold num">{{ number_format($product->cost_price) }}₫</div>
                </div>
            </div>
            <div class="col-md-6">
                <span class="text-muted small d-block mb-1">Phân loại & Giá vốn</span>
                @if($product->variants->isEmpty())
                    <span class="text-muted">—</span>
                @else
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($product->variants as $v)
                            <span class="badge bg-light text-dark border">
                                {{ $v->name }}:
                                @if($v->cost_price > 0)
                                    <span class="text-success fw-semibold">{{ number_format($v->cost_price) }}₫</span>
                                @else
                                    <span class="text-danger">0₫</span>
                                @endif
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Lịch sử phiên bản cũ --}}
@if($product->histories->isEmpty())
    <div class="text-center text-muted py-5">
        <i class="bi bi-clock-history fs-2 d-block mb-2 opacity-25"></i>
        <p>Chưa có lịch sử thay đổi.</p>
        <small>Khi bạn sửa tên sản phẩm, tên phân loại hoặc giá vốn, lịch sử sẽ được lưu tại đây.</small>
    </div>
@else
    <h6 class="text-muted mb-2"><i class="bi bi-clock-history me-1"></i> Lịch sử thay đổi ({{ $product->histories->count() }} phiên bản)</h6>

    @foreach($product->histories as $history)
    @php
        // Tìm phiên bản kế tiếp (version cao hơn, tức là gần hiện tại hơn) để so sánh diff
        $nextHistory = $product->histories->firstWhere('version', $history->version + 1);
        $nextName    = $nextHistory?->name ?? $product->name;
        $nextCost    = $nextHistory ? (float) $nextHistory->cost_price : (float) $product->cost_price;
        $nameChanged = $history->name !== $nextName;
        $costChanged = (float) $history->cost_price !== $nextCost;
    @endphp
    <div class="card mb-2">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <div>
                <span class="badge bg-secondary me-2">v{{ $history->version }}</span>
                <span class="text-muted small">
                    Hiệu lực:
                    <strong>{{ $history->effective_from->format('d/m/Y H:i') }}</strong>
                    →
                    <strong>{{ $history->effective_to?->format('d/m/Y H:i') ?? 'hiện tại' }}</strong>
                </span>
            </div>
            <form action="{{ route('products.histories.destroy', [$product->id, $history->id]) }}"
                  method="POST"
                  data-confirm="Xoá phiên bản v{{ $history->version }}? Đơn hàng từ {{ $history->effective_from->format('d/m/Y') }} đến {{ $history->effective_to?->format('d/m/Y') ?? 'nay' }} sẽ không còn tra được giá vốn theo lịch sử này."
                  onsubmit="return confirm(this.dataset.confirm)">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash"></i> Xoá phiên bản
                </button>
            </form>
        </div>
        <div class="card-body py-2">
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="mb-1">
                        <span class="text-muted small">Tên sản phẩm</span>
                        <div class="{{ $nameChanged ? 'bg-warning-subtle rounded px-1' : '' }} fw-semibold">
                            {{ $history->name }}
                            @if($nameChanged)
                                <i class="bi bi-arrow-right text-warning ms-1" title="Đã đổi thành: {{ $nextName }}"></i>
                                <span class="text-muted" style="font-size:0.8rem">→ {{ $nextName }}</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <span class="text-muted small">Giá vốn mặc định</span>
                        <div class="{{ $costChanged ? 'bg-warning-subtle rounded px-1' : '' }} num fw-semibold">
                            {{ number_format($history->cost_price) }}₫
                            @if($costChanged)
                                <span class="text-muted ms-1" style="font-size:0.8rem">→ {{ number_format($nextCost) }}₫</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <span class="text-muted small d-block">Phân loại & Giá vốn</span>
                    @if($history->variantHistories->isEmpty())
                        <span class="text-muted small">—</span>
                    @else
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            @foreach($history->variantHistories as $vh)
                                @php
                                    // Tìm variant tương ứng ở phiên bản kế để highlight diff
                                    $nextVh     = $nextHistory?->variantHistories->firstWhere('product_variant_id', $vh->product_variant_id)
                                               ?? $product->variants->firstWhere('id', $vh->product_variant_id);
                                    $vNameChanged = $nextVh && $vh->name !== ($nextVh->name ?? $vh->name);
                                    $vCostChanged = $nextVh && (float) $vh->cost_price !== (float) ($nextVh->cost_price ?? $vh->cost_price);
                                    $vChanged     = $vNameChanged || $vCostChanged;
                                @endphp
                                <span class="badge border {{ $vChanged ? 'bg-warning-subtle text-dark border-warning' : 'bg-light text-dark' }}" style="font-size:0.7rem">
                                    {{ $vh->name }}:
                                    @if($vh->cost_price > 0)
                                        <span class="{{ $vCostChanged ? 'text-warning fw-bold' : 'text-success fw-semibold' }}">{{ number_format($vh->cost_price) }}₫</span>
                                    @else
                                        <span class="text-danger">0₫</span>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach
@endif
@endsection
