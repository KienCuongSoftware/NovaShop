@extends('layouts.user')

@section('title', 'Đặt hàng thành công - NovaShop')

@section('content')
<div class="text-center py-5">
    <div class="mb-4 order-success-icon"><svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-success"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
    <h2 class="text-success mb-3">Đặt hàng thành công</h2>
    <p class="text-muted mb-4">Đơn hàng #{{ $order->id }} của bạn đã được tạo. Chúng tôi sẽ xử lý và liên hệ với bạn sớm.</p>
    <div class="d-flex justify-content-center flex-wrap">
        <a href="{{ route('orders.index') }}" class="btn btn-danger mr-2 mb-2">Xem đơn mua</a>
        <a href="{{ route('welcome') }}" class="btn btn-outline-secondary mb-2">Tiếp tục mua sắm</a>
    </div>
</div>
@endsection
