@extends('layouts.admin')

@section('title', 'Đơn hàng #' . $order->id)

@section('content')
<div class="page-header">
    <h2>Đơn hàng #{{ $order->id }}</h2>
    <div class="admin-toolbar">
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">← Danh sách đơn</a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light font-weight-bold">Thông tin giao hàng</div>
            <div class="card-body">
                <p class="mb-1"><strong>Địa chỉ:</strong> {{ $order->shipping_address ?? '—' }}</p>
                <p class="mb-1"><strong>Số điện thoại:</strong> {{ $order->phone ?? '—' }}</p>
                @if($order->notes)
                <p class="mb-0"><strong>Ghi chú:</strong> {{ $order->notes }}</p>
                @endif
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light font-weight-bold">Sản phẩm ({{ $order->items->count() }})</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 70px;">Ảnh</th>
                                <th>Sản phẩm / Phân loại</th>
                                <th class="text-right" style="width: 110px;">Đơn giá</th>
                                <th class="text-center" style="width: 80px;">SL</th>
                                <th class="text-right" style="width: 120px;">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            <tr>
                                <td>
                                    @if($item->product->image ?? null)
                                        <img src="/images/products/{{ basename($item->product->image) }}" alt="" class="img-fluid rounded" style="max-height: 50px; object-fit: contain;">
                                    @else
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center small text-muted" style="width: 50px; height: 50px;">N/A</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="font-weight-bold">{{ $item->product->name ?? '—' }}</div>
                                    @if($item->productVariant)
                                        <small class="text-muted">Phân loại: {{ $item->productVariant->display_name }}</small>
                                    @endif
                                </td>
                                <td class="text-right">{{ number_format($item->price, 0, ',', '.') }}₫</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-right font-weight-bold">{{ number_format($item->price * $item->quantity, 0, ',', '.') }}₫</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer admin-order-summary bg-light">
                @php $lineSubtotal = (float) $order->subtotal; @endphp
                <div class="admin-order-summary__row">
                    <span class="admin-order-summary__label">Tạm tính</span>
                    <span class="admin-order-summary__value">{{ number_format($lineSubtotal, 0, ',', '.') }}₫</span>
                </div>
                @if((int) ($order->discount_amount ?? 0) > 0)
                <div class="admin-order-summary__row text-success">
                    <span class="admin-order-summary__label">Giảm giá @if($order->coupon) ({{ $order->coupon->code }}) @endif</span>
                    <span class="admin-order-summary__value">−{{ number_format($order->discount_amount, 0, ',', '.') }}₫</span>
                </div>
                @endif
                @if((int) ($order->shipping_fee ?? 0) > 0)
                <div class="admin-order-summary__row">
                    <span class="admin-order-summary__label">
                        Phí ship
                        @if($order->shipping_distance_km !== null)
                            <span class="text-muted font-weight-normal">({{ number_format($order->shipping_distance_km, 1, ',', '.') }} km)</span>
                        @endif
                    </span>
                    <span class="admin-order-summary__value">{{ number_format($order->shipping_fee, 0, ',', '.') }}₫</span>
                </div>
                @elseif($order->shipping_distance_km !== null)
                <div class="admin-order-summary__row">
                    <span class="admin-order-summary__label">Khoảng cách</span>
                    <span class="admin-order-summary__value">{{ number_format($order->shipping_distance_km, 1, ',', '.') }} km</span>
                </div>
                @endif
                <div class="admin-order-summary__row admin-order-summary__row--meta">
                    <span class="admin-order-summary__label">Ngày đặt</span>
                    <span class="admin-order-summary__value">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="admin-order-summary__row admin-order-summary__row--total">
                    <span class="admin-order-summary__label">Tổng thanh toán</span>
                    <span class="admin-order-summary__value text-danger">{{ number_format($order->total_amount, 0, ',', '.') }}₫</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light font-weight-bold">Khách hàng</div>
            <div class="card-body">
                <p class="mb-1"><strong>{{ $order->user->name ?? '—' }}</strong></p>
                <p class="mb-0 small text-muted">{{ $order->user->email ?? '—' }}</p>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light font-weight-bold">Thanh toán</div>
            <div class="card-body">
                <p class="mb-1">Phương thức: {{ $order->payment_method === 'paypal' ? 'PayPal' : 'COD' }}</p>
                <p class="mb-1">Vận chuyển: {{ \App\Models\Order::shippingStatusLabel((string) ($order->shipping_status ?? \App\Models\Order::SHIPPING_STATUS_PENDING)) }}</p>
                <p class="mb-0">
                    Trạng thái:
                    @if($order->payment_status === 'paid')
                        <span class="badge badge-success">Đã thanh toán</span>
                    @else
                        <span class="badge badge-secondary">Chưa thanh toán</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light font-weight-bold">Cập nhật trạng thái đơn</div>
            <div class="card-body">
                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group mb-2">
                        <label for="order-status" class="small font-weight-bold">Trạng thái</label>
                        <select name="status" id="order-status" class="form-control" required>
                            @foreach(\App\Models\Order::tabStatusKeys() as $key)
                                <option value="{{ $key }}" {{ $order->status === $key ? 'selected' : '' }}>{{ \App\Models\Order::statusLabel($key) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.admin-order-summary {
    font-size: 0.95rem;
    line-height: 1.45;
}
.admin-order-summary__row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding: 0.35rem 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}
.admin-order-summary__row:last-child {
    border-bottom: 0;
}
.admin-order-summary__label {
    color: #6c757d;
    font-weight: 600;
    flex: 1;
    min-width: 0;
}
.admin-order-summary__value {
    font-weight: 600;
    text-align: right;
    white-space: nowrap;
}
.admin-order-summary__row--meta .admin-order-summary__label,
.admin-order-summary__row--meta .admin-order-summary__value {
    font-weight: 500;
    font-size: 0.95rem;
}
.admin-order-summary__row--total {
    margin-top: 0.25rem;
    padding-top: 0.65rem;
    border-top: 2px solid rgba(0, 0, 0, 0.08);
    border-bottom: 0;
}
.admin-order-summary__row--total .admin-order-summary__label {
    color: #212529;
    font-size: 1rem;
    font-weight: 700;
}
.admin-order-summary__row--total .admin-order-summary__value {
    font-size: 1.05rem;
    font-weight: 700;
}
</style>
@endpush
