@extends('emails.layouts.base')

@section('title', 'Cập nhật đơn hàng — NovaShop')
@section('subtitle', $previousStatus === null ? 'Đơn hàng mới' : 'Trạng thái đơn hàng đã thay đổi')

@section('content')
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

    <table class="meta" cellpadding="0" cellspacing="0" style="width:100%; margin: 16px 0; font-size: 14px;">
        <tr>
            <td style="padding: 6px 0; color:#6c757d;">Tổng tiền</td>
            <td style="padding: 6px 0; text-align:right; font-weight:700;">{{ number_format((float) $order->total_amount, 0, ',', '.') }}₫</td>
        </tr>
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
