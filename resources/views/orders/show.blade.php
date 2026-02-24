@extends('layouts.app')
@section('title', 'Chi tiết Đơn hàng ' . $order->order_code)
@section('breadcrumb', 'Đơn hàng / ' . $order->order_code)

@section('content')
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-bold">Đơn hàng: {{ $order->order_code }}</h5>
    <span class="badge bg-success-subtle text-success">{{ $order->status }}</span>
</div>

<div class="row g-3">
    {{-- Thông tin đơn hàng --}}
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header fw-semibold"><i class="bi bi-info-circle me-1"></i>Thông tin đơn hàng</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th class="text-muted fw-normal" style="width:40%">Mã đơn</th>
                        <td><strong>{{ $order->order_code }}</strong></td></tr>
                    <tr><th class="text-muted fw-normal">Ngày đặt</th>
                        <td>{{ $order->order_date->format('d/m/Y H:i') }}</td></tr>
                    <tr><th class="text-muted fw-normal">Shop</th>
                        <td>{{ $order->shop->name ?? '—' }}</td></tr>
                    <tr><th class="text-muted fw-normal">Mã vận đơn</th>
                        <td>{{ $order->tracking_number ?? '—' }}</td></tr>
                    <tr><th class="text-muted fw-normal">ĐVVC</th>
                        <td>{{ $order->shipping_carrier ?? '—' }}</td></tr>
                    <tr><th class="text-muted fw-normal">Thanh toán</th>
                        <td>{{ $order->payment_method ?? '—' }}</td></tr>
                </table>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold"><i class="bi bi-person me-1"></i>Người mua</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th class="text-muted fw-normal" style="width:40%">Tài khoản</th>
                        <td>{{ $order->buyer_username ?? '—' }}</td></tr>
                    <tr><th class="text-muted fw-normal">Người nhận</th>
                        <td>{{ $order->recipient_name ?? '—' }}</td></tr>
                    <tr><th class="text-muted fw-normal">SĐT</th>
                        <td>{{ $order->phone ?? '—' }}</td></tr>
                    <tr><th class="text-muted fw-normal">Tỉnh/TP</th>
                        <td>{{ $order->province ?? '—' }}</td></tr>
                    @if($order->address)
                    <tr><th class="text-muted fw-normal">Địa chỉ</th>
                        <td style="font-size:0.8rem">{{ $order->address }}</td></tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Tính toán tài chính --}}
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header fw-semibold"><i class="bi bi-table me-1"></i>Chi tiết sản phẩm</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Phân loại</th>
                            <th class="text-end">SL</th>
                            <th class="text-end">Giá vốn</th>
                            <th class="text-end">Giá bán</th>
                            <th class="text-end">Thuế (1.5%)</th>
                            <th class="text-end">Tổng vốn</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td style="max-width:260px">{{ $item->product_name }}</td>
                            <td>{{ $item->variant_name ?? '—' }}</td>
                            <td class="text-end">{{ $item->quantity }}</td>
                            <td class="text-end num">{{ number_format($item->cost_price) }}</td>
                            <td class="text-end num">{{ number_format($item->selling_price) }}</td>
                            <td class="text-end num">{{ number_format($item->tax) }}</td>
                            <td class="text-end num">{{ number_format($item->total_cost) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="4" class="text-end">Tổng giá bán:</th>
                            <th class="text-end num">{{ number_format($order->total_selling_price) }}</th>
                            <th class="text-end num">{{ number_format($order->total_tax) }}</th>
                            <th class="text-end num">{{ number_format($order->total_cost) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header fw-semibold"><i class="bi bi-calculator me-1"></i>Tổng kết tài chính</div>
            <div class="card-body">
                <div class="row g-2">
                    @php
                        $rows = [
                            ['Tổng giá bán', $order->total_selling_price, false, true],
                            ['(-) Phí cố định', $order->fixed_fee, true, false],
                            ['(-) Phí Dịch Vụ', $order->service_fee, true, false],
                            ['(-) Phí thanh toán', $order->payment_fee, true, false],
                            ['(-) Pi Ship', $order->pi_ship, true, false],
                            ['(-) Thuế (1.5%)', $order->total_tax, true, false],
                            ['(-) Tổng vốn', $order->total_cost, true, false],
                        ];
                    @endphp
                    <div class="col-md-6">
                        <table class="table table-sm mb-0">
                            @foreach($rows as [$label, $val, $isDeduction, $isHighlight])
                            <tr class="{{ $isHighlight ? 'table-primary' : '' }}">
                                <td class="{{ $isDeduction ? 'text-danger ps-3' : 'fw-semibold' }}">
                                    {{ $label }}
                                </td>
                                <td class="text-end num {{ $isDeduction ? 'text-danger' : 'fw-semibold' }}">
                                    {{ number_format($val) }}₫
                                </td>
                            </tr>
                            @endforeach
                            <tr class="table-success">
                                <th>= Lợi nhuận</th>
                                <th class="text-end num {{ $order->profit >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($order->profit) }}₫
                                </th>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
