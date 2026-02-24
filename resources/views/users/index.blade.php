@extends('layouts.app')
@section('title', 'Quản lý tài khoản')
@section('breadcrumb', 'Quản lý tài khoản')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Tài khoản người dùng</h5>
        <small class="text-muted">{{ $users->total() }} tài khoản</small>
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-person-plus"></i> Thêm tài khoản
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tên</th>
                    <th>Email</th>
                    <th>Ngày tạo</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td class="text-muted">{{ $user->id }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:32px;height:32px;border-radius:50%;background:#e0f2fe;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <i class="bi bi-person-fill text-primary" style="font-size:.9rem"></i>
                            </div>
                            <span class="fw-semibold">{{ $user->name }}</span>
                            @if($user->id === auth()->id())
                                <span class="badge bg-primary-subtle text-primary" style="font-size:.65rem">Bạn</span>
                            @endif
                        </div>
                    </td>
                    <td class="text-muted">{{ $user->email }}</td>
                    <td class="text-muted">{{ $user->created_at->format('d/m/Y') }}</td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('users.edit', $user->id) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i> Sửa
                            </a>
                            @if($user->id !== auth()->id())
                            <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                  onsubmit="return confirm('Xoá tài khoản {{ addslashes($user->name) }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">Chưa có tài khoản nào.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="card-footer bg-white d-flex justify-content-end">
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection
