@extends('emails.layouts.base')

@section('title', 'Cập nhật đơn hàng — NovaShop')
@section('subtitle', $previousStatus === null ? 'Đơn hàng mới' : 'Trạng thái đơn hàng đã thay đổi')

@section('extra_styles')
    .bill-wrap { margin: 20px 0; }
    .bill-table { width: 100%; border-collapse: collapse; font-size: 14px; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; }
    .bill-table th { background: #f1f3f5; color: #495057; text-align: left; padding: 10px 8px; font-weight: 600; border-bottom: 1px solid #dee2e6; }
    .bill-table th.num { text-align: right; }
    .bill-table th.qty { text-align: center; width: 48px; }
    .bill-table td { padding: 10px 8px; border-bottom: 1px solid #e9ecef; vertical-align: top; }
    .bill-table td.num { text-align: right; white-space: nowrap; }
    .bill-table td.qty { text-align: center; }
    .bill-table tr:last-child td { border-bottom: none; }
    .bill-product-name { font-weight: 600; color: #212529; }
    .bill-variant { font-size: 12px; color: #6c757d; margin-top: 4px; }
    .bill-summary { width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 12px; }
    .bill-summary td { padding: 6px 0; }
    .bill-summary td.label { color: #6c757d; }
    .bill-summary td.val { text-align: right; font-weight: 600; }
    .bill-summary tr.total td { padding-top: 12px; border-top: 2px solid #dee2e6; font-size: 16px; color: #c82333; }
@endsection

@section('content')
    @php
        $items = $order->relationLoaded('items') ? $order->items : $order->items()->with(['product', 'productVariant.attributeValues.attribute'])->get();
        $itemsSubtotal = $items->sum(fn ($i) => (float) $i->price * (int) $i->quantity);
    @endphp

    <p>Xin chào <strong>{{ $order->user?->name ?? 'quý khách' }}</strong>,</p>

    @if($previousStatus === null)
        <p>Cảm ơn bạn đã đặt hàng tại NovaShop. Chúng tôi đã ghi nhận đơn <strong>#{{ $order->id }}</strong>.</p>
    @else
        <p>Đơn hàng <strong>#{{ $order->id }}</strong> của bạn vừa được cập nhật trạng thái.</p>
        <p class="muted" style="margin: 12px 0;">
            Trước: <strong>{{ \App\Models\Order::statusLabel($previousStatus) }}</strong><br>
            Hiện tại: <strong>{{ \App\Models\Order::statusLabel($currentStatus) }}</strong>
        </p>
    @endif

    <div class="bill-wrap">
        <table class="bill-table" cellpadding="0" cellspacing="0" role="presentation">
            <thead>
                <tr>
                    <th style="width:36px;">#</th>
                    <th>Sản phẩm</th>
                    <th class="num">Đơn giá</th>
                    <th class="qty">SL</th>
                    <th class="num">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $idx => $item)
                    @php
                        $vLabel = $item->productVariant?->display_name;
                    @endphp
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div class="bill-product-name">{{ $item->product?->name ?? 'Sản phẩm' }}</div>
                            @if($vLabel && $vLabel !== '—')
                                <div class="bill-variant">Phân loại: {{ $vLabel }}</div>
                            @endif
                        </td>
                        <td class="num">{{ number_format((float) $item->price, 0, ',', '.') }}₫</td>
                        <td class="qty">{{ $item->quantity }}</td>
                        <td class="num">{{ number_format((float) $item->subtotal, 0, ',', '.') }}₫</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="bill-summary" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td class="label">Tạm tính hàng</td>
                <td class="val">{{ number_format($itemsSubtotal, 0, ',', '.') }}₫</td>
            </tr>
            @if((int) ($order->discount_amount ?? 0) > 0)
                <tr>
                    <td class="label">Giảm giá @if($order->coupon)<span style="color:#868e96;">({{ $order->coupon->code }})</span>@endif</td>
                    <td class="val" style="color:#2b8a3e;">−{{ number_format((int) $order->discount_amount, 0, ',', '.') }}₫</td>
                </tr>
            @endif
            <tr>
                <td class="label">Phí vận chuyển</td>
                <td class="val">{{ number_format((int) ($order->shipping_fee ?? 0), 0, ',', '.') }}₫</td>
            </tr>
            <tr class="total">
                <td class="label">Tổng thanh toán</td>
                <td class="val">{{ number_format((float) $order->total_amount, 0, ',', '.') }}₫</td>
            </tr>
        </table>
    </div>

    <table class="meta" cellpadding="0" cellspacing="0" style="width:100%; margin: 16px 0; font-size: 14px;">
        <tr>
            <td style="padding: 6px 0; color:#6c757d;">Thanh toán</td>
            <td style="padding: 6px 0; text-align:right;">{{ $order->payment_method === \App\Models\Order::PAYMENT_METHOD_PAYPAL ? 'PayPal' : ($order->payment_method === \App\Models\Order::PAYMENT_METHOD_MOMO ? 'MoMo' : 'COD') }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 0; color:#6c757d;">Vận chuyển</td>
            <td style="padding: 6px 0; text-align:right;">{{ \App\Models\Order::shippingStatusLabel((string) ($order->shipping_status ?? \App\Models\Order::SHIPPING_STATUS_PENDING)) }}</td>
        </tr>
        @if($order->estimatedDeliveryDateRange())
            <tr>
                <td style="padding: 6px 0; color:#6c757d; vertical-align:top;">Dự kiến nhận hàng</td>
                <td style="padding: 6px 0; text-align:right;">{{ $order->estimatedDeliveryDateLabel() }}</td>
            </tr>
        @endif
    </table>

    @if($currentStatus === \App\Models\Order::STATUS_CANCELLED)
        <p>Đơn hàng đã được hủy. Nếu bạn đã thanh toán trước đó, đội ngũ NovaShop sẽ xử lý hoàn tiền theo chính sách (nếu áp dụng).</p>
    @elseif($currentStatus === \App\Models\Order::STATUS_RETURN_REFUND)
        <p>Yêu cầu trả hàng / hoàn tiền đã được ghi nhận. Chúng tôi sẽ liên hệ hoặc xử lý theo quy trình cửa hàng.</p>
    @elseif($currentStatus === \App\Models\Order::STATUS_COMPLETED)
        <p>Đơn đã hoàn thành. Cảm ơn bạn đã mua sắm tại NovaShop.</p>
    @endif

    <div class="cta-wrap">
        <a href="{{ route('orders.show', $order) }}" class="btn-cta">Xem chi tiết đơn hàng</a>
    </div>
@endsection
