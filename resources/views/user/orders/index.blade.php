@extends('layouts.user')

@section('title', 'Đơn mua - NovaShop')

@section('content')
<div class="page-header mb-4">
    <h2>Đơn mua</h2>
</div>

{{-- Tabs trạng thái đơn --}}
<ul class="nav nav-tabs orders-tabs mb-2">
    <li class="nav-item">
        <a class="nav-link {{ ($status ?? 'all') === 'all' ? 'active' : '' }}" href="{{ route('orders.index', ['status' => 'all', 'shipping_status' => $shippingStatus ?? 'all']) }}">Tất cả</a>
    </li>
    @foreach(\App\Models\Order::tabStatusKeys() as $key)
    <li class="nav-item">
        <a class="nav-link {{ ($status ?? '') === $key ? 'active' : '' }}" href="{{ route('orders.index', ['status' => $key, 'shipping_status' => $shippingStatus ?? 'all']) }}">{{ \App\Models\Order::statusLabel($key) }}</a>
    </li>
    @endforeach
</ul>
{{-- Tabs vận chuyển --}}
<ul class="nav nav-tabs orders-tabs mb-4" style="border-bottom: 1px solid #dee2e6;">
    <li class="nav-item">
        <a class="nav-link {{ ($shippingStatus ?? 'all') === 'all' ? 'active' : '' }}" href="{{ route('orders.index', ['status' => $status ?? 'all', 'shipping_status' => 'all']) }}" style="font-size: 0.9rem;">Vận chuyển: Tất cả</a>
    </li>
    @foreach(\App\Models\Order::tabShippingStatusKeys() as $key)
    <li class="nav-item">
        <a class="nav-link {{ ($shippingStatus ?? '') === $key ? 'active' : '' }}" href="{{ route('orders.index', ['status' => $status ?? 'all', 'shipping_status' => $key]) }}" style="font-size: 0.9rem;">{{ \App\Models\Order::shippingStatusLabel($key) }}</a>
    </li>
    @endforeach
</ul>

{{-- Ô tìm kiếm đơn hàng --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form action="{{ route('orders.index') }}" method="GET" class="d-flex">
            <input type="hidden" name="status" value="{{ $status ?? 'all' }}">
            <input type="hidden" name="shipping_status" value="{{ $shippingStatus ?? 'all' }}">
            <input type="text" name="q" class="form-control rounded" placeholder="Bạn có thể tìm kiếm theo ID đơn hàng hoặc tên sản phẩm" value="{{ request('q') }}">
            <button type="submit" class="btn btn-danger rounded ml-2">Tìm kiếm</button>
        </form>
    </div>
</div>

@if($orders->isEmpty())
<div class="card">
    <div class="card-body text-center py-5">
        <div class="mb-3 empty-orders-icon"><svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ddd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg></div>
        <p class="text-muted mb-3">Chưa có đơn hàng</p>
        <a href="{{ route('welcome') }}" class="btn btn-primary">Mua sắm ngay</a>
    </div>
</div>
@else
@foreach($orders as $order)
<div class="card mb-3 order-card">
    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap">
        <span class="font-weight-bold">Đơn hàng #{{ $order->id }}</span>
        <span class="badge badge-{{ $order->status === 'completed' ? 'success' : (in_array($order->status, ['cancelled', 'return_refund'], true) ? 'secondary' : 'warning') }}">
            {{ \App\Models\Order::statusLabel($order->status) }}
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
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
            <small class="text-muted">
                Địa chỉ: {{ $order->shipping_address }} | SĐT: {{ $order->phone }}
                <br>Ngày đặt: {{ $order->created_at->format('d/m/Y H:i') }} | Vận chuyển: {{ \App\Models\Order::shippingStatusLabel((string) ($order->shipping_status ?? \App\Models\Order::SHIPPING_STATUS_PENDING)) }}
            </small>
            <span class="font-weight-bold">Tổng: <span class="text-danger">{{ number_format($order->total_amount, 0, ',', '.') }}₫</span></span>
        </div>
        @if($order->canShowPayButton() || $order->canCancel())
            <div class="mt-2">
                @if($order->canShowPayButton())
                    <a href="{{ route('paypal.create-order', $order) }}" class="btn btn-danger btn-sm mr-2">Thanh toán</a>
                @endif
                @if($order->canCancel())
                    <form action="{{ route('orders.cancel', $order) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn hủy đơn hàng #{{ $order->id }}?');">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Hủy đơn</button>
                    </form>
                @endif
            </div>
        @endif
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
.empty-orders-icon { font-size: 4rem; color: #ddd; line-height: 1; }
.empty-orders-icon svg { display: block; margin: 0 auto; }
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
