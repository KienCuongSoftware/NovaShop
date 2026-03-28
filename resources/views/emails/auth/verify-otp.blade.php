@extends('emails.layouts.base')

@section('title', 'OTP xác thực email - NovaShop')
@section('subtitle', 'Xác thực email tài khoản')

@section('extra_styles')
        .otp-box {
            margin: 18px 0;
            padding: 16px;
            border: 1px dashed #dc3545;
            background-color: #fff5f5;
            border-radius: 10px;
            text-align: center;
        }
        .otp-label {
            margin: 0 0 8px;
            color: #6c757d;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .otp-code {
            margin: 0;
            font-size: 32px;
            line-height: 1.1;
            font-weight: 800;
            letter-spacing: 6px;
            color: #dc3545;
        }
        .notice {
            margin-top: 16px;
            padding: 12px 14px;
            border-left: 4px solid #17a2b8;
            background-color: #e8f7fb;
            color: #0c5460;
            border-radius: 6px;
            font-size: 14px;
        }
        .cta-wrap {
            margin-top: 20px;
            text-align: center;
        }
        .btn-cta {
            display: inline-block;
            background: #dc3545;
            color: #fff !important;
            text-decoration: none;
            font-weight: 700;
            border-radius: 8px;
            padding: 12px 22px;
            font-size: 15px;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.25);
        }
        .btn-cta:hover {
            background: #c82333;
            color: #fff !important;
        }
        .help-link {
            margin-top: 10px;
            font-size: 13px;
            color: #6c757d;
        }
        .help-link a {
            color: #dc3545;
            text-decoration: none;
        }
        @media only screen and (max-width: 640px) {
            .otp-code {
                font-size: 28px;
                letter-spacing: 4px;
            }
        }
@endsection

@section('content')
@php
    $verifyUrl = route('verification.otp.notice');
@endphp
<p>Xin chào <strong>{{ $name }}</strong>,</p>
<p>
    Cảm ơn bạn đã đăng ký tài khoản tại <strong>NovaShop</strong>.
    Vui lòng nhập mã OTP bên dưới để hoàn tất xác thực email:
</p>

<div class="otp-box">
    <p class="otp-label">Mã OTP của bạn</p>
    <p class="otp-code">{{ $otp }}</p>
</div>

<p>
    Mã có hiệu lực đến <strong>{{ $expiresAt }}</strong>.
    Sau thời gian này, bạn có thể yêu cầu gửi lại mã mới.
</p>

<div class="notice">
    Nếu bạn không thực hiện đăng ký tài khoản, vui lòng bỏ qua email này.
    Để bảo mật, không chia sẻ mã OTP cho bất kỳ ai.
</div>

<div class="cta-wrap">
    <a href="{{ $verifyUrl }}" class="btn-cta">Xác thực ngay</a>
    <div class="help-link">
        Nút không mở được? Truy cập:
        <a href="{{ $verifyUrl }}">{{ $verifyUrl }}</a>
    </div>
</div>
@endsection
