@extends('emails.layouts.base')

@section('title', 'Đánh giá chưa được duyệt - NovaShop')
@section('subtitle', 'Thông báo duyệt đánh giá sản phẩm')

@section('extra_styles')
        .notice-danger {
            margin-top: 16px;
            padding: 12px 14px;
            border-left: 4px solid #dc3545;
            background-color: #fff5f5;
            color: #7a1b22;
            border-radius: 6px;
            font-size: 14px;
        }
        .review-box {
            margin: 18px 0 0;
            padding: 14px 16px;
            border: 1px solid #f1f3f5;
            background: #fafbfc;
            border-radius: 10px;
        }
        .review-title { font-weight: 800; margin: 0 0 6px; }
@endsection

@section('content')
@php
    $userName = $review->user?->name ?: 'bạn';
    $productName = $review->product?->name ?: 'sản phẩm';
    $productUrl = $review->product ? route('products.show', $review->product) : url('/');
    $reason = trim((string) ($review->rejection_reason ?? ''));
@endphp
<p>Xin chào <strong>{{ $userName }}</strong>,</p>
<p>
    Đánh giá của bạn cho sản phẩm <strong>{{ $productName }}</strong> hiện <strong>chưa được duyệt</strong>.
</p>

<div class="notice-danger">
    @if($reason !== '')
        <strong>Lý do:</strong> {{ $reason }}
    @else
        Đánh giá của bạn chưa phù hợp với tiêu chí kiểm duyệt hiện tại.
    @endif
</div>

<div class="review-box">
    <div class="review-title">Nội dung bạn đã gửi</div>
    @if(!empty($review->title))
        <div><strong>{{ $review->title }}</strong></div>
    @endif
    <div style="white-space: pre-line; margin-top: 6px;">{{ $review->content }}</div>
    <div class="muted" style="margin-top: 10px; font-size: 13px;">
        Bạn có thể chỉnh sửa và gửi lại đánh giá trực tiếp trên trang sản phẩm.
    </div>
</div>

<div class="cta-wrap">
    <a href="{{ $productUrl }}" class="btn-cta">Mở trang sản phẩm</a>
    <div class="help-link">
        Nút không mở được? Truy cập:
        <a href="{{ $productUrl }}">{{ $productUrl }}</a>
    </div>
</div>
@endsection

