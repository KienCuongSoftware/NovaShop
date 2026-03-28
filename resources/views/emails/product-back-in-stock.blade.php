@extends('emails.layouts.base')

@section('title', 'Sản phẩm đã có hàng lại - NovaShop')
@section('subtitle', 'Thông báo có hàng')

@section('extra_styles')
    .product-box {
        margin-top: 14px;
        padding: 14px 16px;
        border: 1px solid #f1f3f5;
        background: #fafbfc;
        border-radius: 10px;
    }
    .product-name {
        font-weight: 800;
        font-size: 16px;
        margin: 0 0 6px;
    }
    .pill {
        display: inline-block;
        padding: 6px 10px;
        border-radius: 999px;
        background: #fff5f5;
        border: 1px solid rgba(220, 53, 69, 0.25);
        color: #7a1b22;
        font-size: 13px;
        font-weight: 600;
    }
@endsection

@section('content')
@php
    $name = $user?->name ? (' '.$user->name) : '';
    $url = route('products.show', $product);
@endphp

<p>Xin chào{{ $name }},</p>
<p>Sản phẩm bạn đăng ký thông báo đã <strong>có hàng lại</strong>:</p>

<div class="product-box">
    <div class="product-name">{{ $product->name }}</div>
    @if($variant)
        <div class="muted" style="margin-top:6px;">
            Biến thể: <span class="pill">{{ $variant->display_name ?? ('#'.$variant->id) }}</span>
        </div>
    @endif
</div>

<div class="cta-wrap">
    <a href="{{ $url }}" class="btn-cta">Xem sản phẩm</a>
    <div class="help-link">
        Nút không mở được? Truy cập:
        <a href="{{ $url }}">{{ $url }}</a>
    </div>
</div>
@endsection
