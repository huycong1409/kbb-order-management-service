@extends('layouts.app')
@section('title', 'Quản lý Shop')
@section('breadcrumb', 'Quản lý Shop')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Danh sách Shop</h5>
        <small class="text-muted">{{ $shops->total() }} shop</small>
    </div>
    <a href="{{ route('shops.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Thêm Shop
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tên Shop</th>
                    <th>Platform</th>
                    <th>URL</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shops as $shop)
                <tr>
                    <td class="text-muted">{{ $shop->id }}</td>
                    <td>
                        <div class="fw-semibold">{{ $shop->name }}</div>
                        @if($shop->description)
                            <small class="text-muted">{{ Str::limit($shop->description, 60) }}</small>
                        @endif
                    </td>
                    <td>
                        @php
                            $platformColors = ['shopee' => 'danger', 'lazada' => 'primary', 'tiki' => 'info', 'sendo' => 'warning', 'other' => 'secondary'];
                            $color = $platformColors[$shop->platform] ?? 'secondary';
                        @endphp
                        <span class="badge bg-{{ $color }} badge-platform">{{ strtoupper($shop->platform) }}</span>
                    </td>
                    <td>
                        @if($shop->url)
                            <a href="{{ $shop->url }}" target="_blank" class="text-decoration-none text-muted" style="font-size:0.8rem">
                                <i class="bi bi-link-45deg"></i> {{ Str::limit($shop->url, 40) }}
                            </a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($shop->is_active)
                            <span class="badge bg-success-subtle text-success">Hoạt động</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">Tắt</span>
                        @endif
                    </td>
                    <td class="text-muted">{{ $shop->created_at->format('d/m/Y') }}</td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('shops.products.index', $shop->id) }}"
                               class="btn btn-sm btn-outline-secondary" title="Sản phẩm">
                                <i class="bi bi-box"></i> Sản phẩm
                            </a>
                            <a href="{{ route('shops.edit', $shop->id) }}"
                               class="btn btn-sm btn-outline-primary" title="Sửa">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('shops.destroy', $shop->id) }}" method="POST"
                                  onsubmit="return confirm('Xoá shop {{ addslashes($shop->name) }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Xoá">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-shop fs-2 d-block mb-2 opacity-25"></i>
                        Chưa có shop nào. <a href="{{ route('shops.create') }}">Tạo shop đầu tiên</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($shops->hasPages())
    <div class="card-footer bg-white d-flex justify-content-end">
        {{ $shops->links() }}
    </div>
    @endif
</div>
@endsection
