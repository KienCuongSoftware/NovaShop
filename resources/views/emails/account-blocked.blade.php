@extends('emails.layouts.base')

@section('title', 'Tài khoản đã bị tạm khóa - NovaShop')
@section('subtitle', 'Thông báo bảo mật tài khoản')

@section('extra_styles')
        .alert-box {
            margin: 18px 0;
            padding: 14px 16px;
            border-left: 4px solid #dc3545;
            background-color: #fff5f5;
            border-radius: 8px;
            font-size: 14px;
            color: #721c24;
        }
@endsection

@section('content')
<p>Xin chào <strong>{{ $userName }}</strong>,</p>

<p>
    Tài khoản NovaShop gắn với địa chỉ email <strong>{{ $userEmail }}</strong>
    <strong>đã bị tạm khóa</strong> bởi quản trị viên.
</p>

<div class="alert-box">
    Bạn <strong>không thể đăng nhập</strong> vào website cho đến khi tài khoản được mở lại.
    Nếu bạn cho rằng đây là nhầm lẫn, vui lòng liên hệ bộ phận hỗ trợ của NovaShop (qua hotline/email trên website).
</div>

<p class="muted" style="margin-top: 20px; font-size: 14px;">
    Thời điểm gửi thông báo: {{ now()->timezone(config('app.timezone'))->format('H:i d/m/Y') }}.
</p>
@endsection
