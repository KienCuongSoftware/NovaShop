@extends('layouts.user')

@section('title', 'Giỏ hàng - NovaShop')

@section('content')
<div class="page-header mb-4">
    <h2>Giỏ hàng</h2>
</div>

@if($cart->items->isEmpty())
<div class="card">
    <div class="card-body text-center py-5">
        <p class="text-muted mb-3">Giỏ hàng trống.</p>
        <div class="d-flex justify-content-center gap-2 flex-wrap">
            <a href="{{ route('welcome') }}" class="btn btn-primary">Mua sắm ngay</a>
            <a href="{{ route('orders.index') }}" class="btn btn-outline-primary">Xem đơn hàng</a>
        </div>
    </div>
</div>
@else
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 80px;">Ảnh</th>
                        <th>Sản phẩm</th>
                        <th class="text-right">Đơn giá</th>
                        <th class="text-center" style="width: 140px;">Số lượng</th>
                        <th class="text-right">Thành tiền</th>
                        <th style="width: 60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cart->items as $item)
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
                        <td class="text-right">{{ number_format($item->product->price, 0, ',', '.') }}₫</td>
                        <td class="text-center">
                            <form action="{{ route('cart.update') }}" method="POST" class="d-inline">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="cart_item_id" value="{{ $item->id }}">
                                <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ $item->product->quantity }}" class="form-control form-control-sm text-center d-inline-block" style="width: 70px;" onchange="this.form.submit()">
                            </form>
                        </td>
                        <td class="text-right font-weight-bold text-danger">{{ number_format($item->subtotal, 0, ',', '.') }}₫</td>
                        <td>
                            <form action="{{ route('cart.remove', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa sản phẩm này khỏi giỏ hàng?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa">&times;</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <span class="font-weight-bold">Tổng cộng: <span class="text-danger">{{ number_format($cart->items->sum(fn($i) => $i->subtotal), 0, ',', '.') }}₫</span></span>
            <div class="d-flex mt-2 mt-md-0">
                <a href="{{ route('welcome') }}" class="btn btn-outline-secondary mr-2">Tiếp tục mua sắm</a>
                <a href="{{ route('checkout.show') }}" class="btn btn-danger">Mua hàng</a>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
