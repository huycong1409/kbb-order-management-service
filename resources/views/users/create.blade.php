@extends('layouts.app')
@section('title', 'Thêm tài khoản')
@section('breadcrumb', 'Quản lý tài khoản / Thêm mới')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-bold">Thêm tài khoản mới</h5>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header fw-semibold">Thông tin tài khoản</div>
            <div class="card-body">
                <form action="{{ route('users.store') }}" method="POST">
                    @csrf
                    @include('users._form')
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Tạo tài khoản
                        </button>
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Huỷ</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
