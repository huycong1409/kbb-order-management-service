@extends('layouts.app')
@section('title', 'Thêm Shop')
@section('breadcrumb', 'Quản lý Shop / Thêm mới')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('shops.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-bold">Thêm Shop mới</h5>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <span class="fw-semibold">Thông tin Shop</span>
            </div>
            <div class="card-body">
                <form action="{{ route('shops.store') }}" method="POST">
                    @csrf
                    @include('shops._form')
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Tạo Shop
                        </button>
                        <a href="{{ route('shops.index') }}" class="btn btn-outline-secondary">Huỷ</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
