@extends('layouts.user')

@section('title', 'Đơn mua - NovaShop')

@section('content')
<div class="page-header mb-4">
    <h2>Đơn mua</h2>
</div>

{{-- Tabs giống Shopee --}}
<ul class="nav nav-tabs orders-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link {{ ($status ?? 'all') === 'all' ? 'active' : '' }}" href="{{ route('orders.index', ['status' => 'all']) }}">Tất cả</a>
    </li>
    @foreach(\App\Models\Order::statusLabels() as $key => $label)
    <li class="nav-item">
        <a class="nav-link {{ ($status ?? '') === $key ? 'active' : '' }}" href="{{ route('orders.index', ['status' => $key]) }}">{{ $label }}</a>
    </li>
    @endforeach
</ul>

{{-- Ô tìm kiếm đơn hàng --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form action="{{ route('orders.index') }}" method="GET" class="d-flex">
            <input type="hidden" name="status" value="{{ $status ?? 'all' }}">
            <input type="text" name="q" class="form-control" placeholder="Bạn có thể tìm kiếm theo ID đơn hàng hoặc tên sản phẩm" value="{{ request('q') }}">
            <button type="submit" class="btn btn-danger ml-2">Tìm kiếm</button>
        </form>
    </div>
</div>

@if($orders->isEmpty())
<div class="card">
    <div class="card-body text-center py-5">
        <div class="mb-3" style="font-size: 4rem; color: #ddd;">📋</div>
        <p class="text-muted mb-3">Chưa có đơn hàng</p>
        <a href="{{ route('welcome') }}" class="btn btn-primary">Mua sắm ngay</a>
    </div>
</div>
@else
@foreach($orders as $order)
<div class="card mb-3 order-card">
    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap">
        <span class="font-weight-bold">Đơn hàng #{{ $order->id }}</span>
        <span class="badge badge-{{ $order->status === 'completed' ? 'success' : ($order->status === 'cancelled' || $order->status === 'return_refund' ? 'secondary' : 'warning') }}">
            {{ \App\Models\Order::statusLabels()[$order->status] ?? $order->status }}
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 80px;">Ảnh</th>
                        <th>Sản phẩm</th>
                        <th class="text-right">Đơn giá</th>
                        <th class="text-center" style="width: 100px;">Số lượng</th>
                        <th class="text-right">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>
                            @if($item->product->image)
                                <img src="/images/products/{{ basename($item->product->image) }}" alt="{{ $item->product->name }}" class="img-fluid rounded" style="max-height: 60px; object-fit: contain;">
                            @else
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 0.75rem; color: #999;">N/A</div>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('products.show', $item->product) }}" class="font-weight-bold text-dark">{{ $item->product->name }}</a>
                        </td>
                        <td class="text-right">{{ number_format($item->price, 0, ',', '.') }}₫</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right font-weight-bold text-danger">{{ number_format($item->subtotal, 0, ',', '.') }}₫</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap">
        <small class="text-muted">
            Địa chỉ: {{ $order->shipping_address }} | SĐT: {{ $order->phone }}
            <br>Ngày đặt: {{ $order->created_at->format('d/m/Y H:i') }}
        </small>
        <span class="font-weight-bold">Tổng: <span class="text-danger">{{ number_format($order->total_amount, 0, ',', '.') }}₫</span></span>
    </div>
</div>
@endforeach

@if($orders->hasPages())
<div class="d-flex justify-content-center mt-4">
    {{ $orders->links() }}
</div>
@endif
@endif

<style>
.orders-tabs .nav-link {
    color: #333;
    border: none;
    border-bottom: 3px solid transparent;
    padding: 0.75rem 1rem;
}
.orders-tabs .nav-link:hover {
    color: #dc3545;
}
.orders-tabs .nav-link.active {
    color: #dc3545;
    font-weight: 600;
    border-bottom-color: #dc3545;
    background: transparent;
}
</style>
@endsection
