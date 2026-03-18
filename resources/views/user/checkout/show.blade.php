@extends('layouts.user')

@section('title', 'Xác nhận đơn hàng - NovaShop')

@section('content')
<div class="page-header mb-4">
    <h2>Xác nhận đơn hàng</h2>
    <a href="{{ route('cart.index') }}" class="btn btn-outline-secondary">← Giỏ hàng</a>
</div>

<form action="{{ route('checkout.place-order') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><strong>Thông tin giao hàng</strong></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Họ tên <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $user->name ?? '') }}" required placeholder="Nguyễn Văn A">
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required placeholder="0912345678">
                    </div>
                    <div class="form-group">
                        <label>Địa chỉ giao hàng <span class="text-danger">*</span></label>
                        <input type="text" name="shipping_address" class="form-control" value="{{ old('shipping_address') }}" required placeholder="Số nhà, đường, quận/huyện, tỉnh/thành">
                    </div>
                    <div class="form-group mb-0">
                        <label>Ghi chú</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Ghi chú cho đơn hàng">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header"><strong>Phương thức thanh toán</strong></div>
                <div class="card-body">
                    <div class="form-group mb-0">
                        <div class="custom-control custom-radio mb-2">
                            <input type="radio" id="pm_cod" name="payment_method" value="cod" class="custom-control-input" {{ old('payment_method', 'cod') === 'cod' ? 'checked' : '' }}>
                            <label class="custom-control-label" for="pm_cod">COD (Thanh toán khi nhận hàng)</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="pm_paypal" name="payment_method" value="paypal" class="custom-control-input" {{ old('payment_method') === 'paypal' ? 'checked' : '' }}>
                            <label class="custom-control-label" for="pm_paypal">PayPal</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><strong>Đơn hàng</strong></div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($cart->items as $item)
                        @php
                            $fi = ($activeFlashSale ?? null) && $item->product_variant_id ? $activeFlashSale->items->firstWhere('product_variant_id', $item->product_variant_id) : null;
                            $unitP = $fi && $fi->remaining > 0 ? (float) $fi->sale_price : ($item->productVariant ? (float) $item->productVariant->price : (float) $item->product->price);
                            $lineTotal = $unitP * $item->quantity;
                        @endphp
                        <li class="list-group-item d-flex align-items-center">
                            @if($item->product->image)
                                <img src="/images/products/{{ basename($item->product->image) }}" alt="" class="rounded mr-3" style="width: 50px; height: 50px; object-fit: cover;">
                            @else
                                <div class="rounded mr-3 bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">—</div>
                            @endif
                            <div class="flex-grow-1">
                                <div class="font-weight-bold">{{ $item->product->name }}</div>
                                @if($item->variant_display)
                                    <small class="text-muted d-block">{{ $item->variant_display }}</small>
                                @endif
                                <small class="text-muted">{{ number_format($unitP, 0, ',', '.') }}₫ × {{ $item->quantity }}{!! $fi && $fi->remaining > 0 ? ' <span class="badge badge-danger">Flash</span>' : '' !!}</small>
                            </div>
                            <span class="text-danger font-weight-bold">{{ number_format($lineTotal, 0, ',', '.') }}₫</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Tạm tính</span>
                        <span>{{ number_format($total, 0, ',', '.') }}₫</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center font-weight-bold text-danger h5 mb-0">
                        <span>Tổng tiền</span>
                        <span>{{ number_format($total, 0, ',', '.') }}₫</span>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-danger btn-block btn-lg">Đặt hàng</button>
        </div>
    </div>
</form>
@endsection
