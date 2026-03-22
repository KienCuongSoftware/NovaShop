<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: sans-serif; line-height: 1.5; color: #111;">
    <p>Xin chào{{ $user?->name ? ' '.$user->name : '' }},</p>
    <p>Sản phẩm bạn đăng ký thông báo đã <strong>có hàng lại</strong>:</p>
    <p><strong>{{ $product->name }}</strong></p>
    @if($variant)
        <p>Biến thể: {{ $variant->display_name ?? ('#'.$variant->id) }}</p>
    @endif
    <p>
        <a href="{{ url(route('products.show', $product)) }}" style="color: #dc3545;">Xem sản phẩm</a>
    </p>
    <p style="color:#666;font-size:12px;">NovaShop</p>
</body>
</html>
