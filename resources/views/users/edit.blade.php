@extends('layouts.app')
@section('title', 'Sửa tài khoản')
@section('breadcrumb', 'Quản lý tài khoản / Chỉnh sửa')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-bold">Sửa: {{ $user->name }}</h5>
    @if($user->id === auth()->id())
        <span class="badge bg-primary-subtle text-primary">Tài khoản của bạn</span>
    @endif
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header fw-semibold">Thông tin tài khoản</div>
            <div class="card-body">
                <form action="{{ route('users.update', $user->id) }}" method="POST">
                    @csrf @method('PUT')
                    @include('users._form', ['user' => $user])
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Lưu thay đổi
                        </button>
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Huỷ</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
