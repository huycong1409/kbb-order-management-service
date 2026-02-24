@extends('layouts.app')
@section('title', 'Sửa Shop')
@section('breadcrumb', 'Quản lý Shop / Chỉnh sửa')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('shops.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-bold">Sửa Shop: {{ $shop->name }}</h5>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <span class="fw-semibold">Thông tin Shop</span>
            </div>
            <div class="card-body">
                <form action="{{ route('shops.update', $shop->id) }}" method="POST">
                    @csrf @method('PUT')
                    @include('shops._form', ['shop' => $shop])
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Lưu thay đổi
                        </button>
                        <a href="{{ route('shops.index') }}" class="btn btn-outline-secondary">Huỷ</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
