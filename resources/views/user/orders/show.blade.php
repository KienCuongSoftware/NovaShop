@extends('layouts.user')

@section('title', 'Chi tiết đơn #' . $order->id . ' - NovaShop')

@section('content')
<div class="page-header mb-4 d-flex flex-wrap align-items-center justify-content-between" style="gap: 1rem;">
    <div>
        <nav class="small text-muted mb-1">
            <a href="{{ route('orders.index') }}" class="text-muted">Đơn mua</a>
            <span class="mx-1">/</span>
            <span>#{{ $order->id }}</span>
        </nav>
        <h2 class="mb-0">Chi tiết đơn hàng #{{ $order->id }}</h2>
    </div>
    <span class="badge badge-{{ $order->status === 'completed' ? 'success' : (in_array($order->status, ['cancelled', 'return_refund'], true) ? 'secondary' : 'warning') }}" style="font-size: 0.95rem; padding: 0.5rem 0.75rem;">
        {{ \App\Models\Order::statusLabel($order->status) }}
    </span>
</div>

@php
    $canReviewItems = $order->allowsProductReviews();
    $deliveryRange = $order->estimatedDeliveryDateRange();
@endphp

@if($deliveryRange && !in_array($order->status, ['cancelled', 'return_refund'], true))
    <div class="alert alert-info border-0 mb-4" style="border-radius: 0.75rem;">
        <strong>Dự kiến nhận hàng:</strong> {{ $order->estimatedDeliveryDateLabel() }}
        <span class="d-block small mt-1 text-muted">Ước tính theo khoảng cách giao hàng; có thể thay đổi khi đơn được xử lý.</span>
    </div>
@endif

@if($canReviewItems)
    <div class="alert alert-success border-0 mb-4" style="border-radius: 0.75rem;">
        Đơn đã giao thành công. Bạn có thể <strong>đánh giá từng sản phẩm</strong> bằng nút bên dưới.
    </div>
@endif

<div class="card mb-3 order-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 80px;">Ảnh</th>
                        <th>Sản phẩm</th>
                        <th class="text-right">Đơn giá</th>
                        <th class="text-center" style="width: 90px;">SL</th>
                        <th class="text-right">Thành tiền</th>
                        @if($canReviewItems)
                            <th class="text-center" style="width: 130px;">Đánh giá</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>
                            @if($item->product && $item->product->image)
                                <img src="/images/products/{{ basename($item->product->image) }}" alt="{{ $item->product->name }}" class="img-fluid rounded" style="max-height: 60px; object-fit: contain;">
                            @else
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 0.75rem; color: #999;">N/A</div>
                            @endif
                        </td>
                        <td>
                            @if($item->product)
                                <a href="{{ route('products.show', $item->product) }}" class="font-weight-bold text-dark">{{ $item->product->name }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($item->price, 0, ',', '.') }}₫</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right font-weight-bold text-danger">{{ number_format($item->subtotal, 0, ',', '.') }}₫</td>
                        @if($canReviewItems && $item->product)
                            <td class="text-center align-middle">
                                <a href="{{ route('products.show', $item->product) }}#product-reviews-block" class="btn btn-sm btn-outline-danger">Đánh giá</a>
                            </td>
                        @elseif($canReviewItems)
                            <td class="text-center text-muted small">—</td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-2" style="gap: 1rem;">
            <small class="text-muted">
                Địa chỉ: {{ $order->shipping_address }} | SĐT: {{ $order->phone }}
                <br>Ngày đặt: {{ $order->created_at->format('d/m/Y H:i') }} | Vận chuyển: {{ \App\Models\Order::shippingStatusLabel((string) ($order->shipping_status ?? \App\Models\Order::SHIPPING_STATUS_PENDING)) }}
                @if($order->shipping_distance_km !== null)
                    <br>Khoảng cách giao: {{ number_format($order->shipping_distance_km, 1, ',', '.') }} km
                @endif
            </small>
            <span class="font-weight-bold">Tổng: <span class="text-danger">{{ number_format($order->total_amount, 0, ',', '.') }}₫</span></span>
        </div>
        <div class="d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
            <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary btn-sm">← Danh sách đơn</a>
            @if($order->canShowPayButton())
                <a href="{{ route('paypal.create-order', $order) }}" class="btn btn-danger btn-sm">Thanh toán</a>
            @endif
            @if($order->canCancel())
                <form action="{{ route('orders.cancel', $order) }}" method="POST" class="d-inline" onsubmit="return bsConfirmSubmit(this, 'Bạn có chắc muốn hủy đơn hàng #{{ $order->id }}? Hàng sẽ được hoàn lại kho.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Hủy đơn</button>
                </form>
            @endif
            @if($order->canRequestReturn())
                <form action="{{ route('orders.request-return', $order) }}" method="POST" class="d-inline" onsubmit="return bsConfirmSubmit(this, 'Gửi yêu cầu trả hàng / hoàn tiền cho đơn #{{ $order->id }}? Hàng trong đơn sẽ được nhập lại kho; cửa hàng sẽ xử lý hoàn tiền theo chính sách.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">Trả hàng / Hoàn tiền</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
