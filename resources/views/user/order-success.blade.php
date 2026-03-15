@extends('layouts.user')

@section('title', 'Đặt hàng thành công - NovaShop')

@section('content')
<div class="text-center py-5">
    <div class="mb-4" style="font-size: 4rem;">✓</div>
    <h2 class="text-success mb-3">Đặt hàng thành công</h2>
    <p class="text-muted mb-4">Đơn hàng #{{ $order->id }} của bạn đã được tạo. Chúng tôi sẽ xử lý và liên hệ với bạn sớm.</p>
    <div class="d-flex justify-content-center flex-wrap">
        <a href="{{ route('orders.index') }}" class="btn btn-danger mr-2 mb-2">Xem đơn mua</a>
        <a href="{{ route('welcome') }}" class="btn btn-outline-secondary mb-2">Tiếp tục mua sắm</a>
    </div>
</div>
@endsection
