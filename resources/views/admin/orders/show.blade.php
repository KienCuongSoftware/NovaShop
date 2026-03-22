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
            <div class="card-footer">
                @php $lineSubtotal = (float) $order->subtotal; @endphp
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted">Tạm tính</span>
                    <span>{{ number_format($lineSubtotal, 0, ',', '.') }}₫</span>
                </div>
                @if((int) ($order->discount_amount ?? 0) > 0)
                <div class="d-flex justify-content-between align-items-center mb-1 text-success">
                    <span>Giảm giá @if($order->coupon) ({{ $order->coupon->code }}) @endif</span>
                    <span>−{{ number_format($order->discount_amount, 0, ',', '.') }}₫</span>
                </div>
                @endif
                @if((int) ($order->shipping_fee ?? 0) > 0)
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted">Phí ship</span>
                    <span>{{ number_format($order->shipping_fee, 0, ',', '.') }}₫</span>
                </div>
                @if($order->shipping_distance_km !== null)
                <div class="d-flex justify-content-between align-items-center mb-1 small text-muted">
                    <span>Khoảng cách</span>
                    <span>{{ number_format($order->shipping_distance_km, 1) }} km</span>
                </div>
                @endif
                @endif
                <div class="d-flex justify-content-between align-items-center pt-1">
                    <span class="text-muted">Ngày đặt: {{ $order->created_at->format('d/m/Y H:i') }}</span>
                    <span class="font-weight-bold">Tổng: <span class="text-danger">{{ number_format($order->total_amount, 0, ',', '.') }}₫</span></span>
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
