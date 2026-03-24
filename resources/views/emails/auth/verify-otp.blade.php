<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP xác thực email - NovaShop</title>
    <style>
        /* Email-safe, bootstrap-inspired utility styling */
        body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            color: #212529;
        }
        .container {
            width: 100%;
            max-width: 640px;
            margin: 24px auto;
            background-color: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(90deg, #dc3545, #c82333);
            color: #ffffff;
            padding: 18px 24px;
        }
        .brand-row {
            display: table;
            width: 100%;
        }
        .brand-cell {
            display: table-cell;
            vertical-align: middle;
        }
        .brand-logo {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: #fff;
            padding: 4px;
            display: inline-block;
            margin-right: 8px;
            vertical-align: middle;
        }
        .brand {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.2px;
            display: inline-block;
            vertical-align: middle;
        }
        .subtitle {
            margin: 6px 0 0;
            font-size: 14px;
            opacity: 0.95;
        }
        .content {
            padding: 24px;
            line-height: 1.6;
            font-size: 15px;
        }
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
        .footer {
            padding: 16px 24px 22px;
            color: #6c757d;
            font-size: 13px;
            border-top: 1px solid #f1f3f5;
            background-color: #fafbfc;
        }
        .muted {
            color: #6c757d;
        }
        @media only screen and (max-width: 640px) {
            .container {
                margin: 0;
                border-radius: 0;
                border-left: 0;
                border-right: 0;
            }
            .content, .header, .footer {
                padding-left: 16px;
                padding-right: 16px;
            }
            .otp-code {
                font-size: 28px;
                letter-spacing: 4px;
            }
        }
    </style>
</head>
<body>
    @php
        $verifyUrl = route('verification.otp.notice');
        $logoUrl = rtrim(config('app.url'), '/') . '/favicon.svg';
    @endphp
    <div class="container" role="article" aria-label="NovaShop OTP Email">
        <div class="header">
            <div class="brand-row">
                <div class="brand-cell">
                    <img src="{{ $logoUrl }}" alt="NovaShop" class="brand-logo">
                    <h1 class="brand">NovaShop</h1>
                </div>
            </div>
            <p class="subtitle">Xác thực email tài khoản</p>
        </div>

        <div class="content">
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
        </div>

        <div class="footer">
            <div>Trân trọng,</div>
            <div><strong>Đội ngũ NovaShop</strong></div>
            <div class="muted">Email này được gửi tự động, vui lòng không trả lời trực tiếp.</div>
        </div>
    </div>
</body>
</html>
