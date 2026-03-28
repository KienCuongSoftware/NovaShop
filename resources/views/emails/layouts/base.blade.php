<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'NovaShop')</title>
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
        /* Monogram thay vì <img>: Gmail không tải localhost; nhiều client chặn SVG trong img */
        .brand-logo {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: #fff;
            display: inline-block;
            margin-right: 8px;
            vertical-align: middle;
            text-align: center;
            line-height: 34px;
            font-weight: 800;
            font-size: 15px;
            color: #dc3545;
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
        @yield('extra_styles')
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
        }
    </style>
</head>
<body>
    <div class="container" role="article" aria-label="NovaShop Email">
        <div class="header">
            <div class="brand-row">
                <div class="brand-cell">
                    <span class="brand-logo" role="img" aria-label="NovaShop">N</span>
                    <h1 class="brand">NovaShop</h1>
                </div>
            </div>
            <p class="subtitle">@yield('subtitle')</p>
        </div>

        <div class="content">
            @yield('content')
        </div>

        <div class="footer">
            <div>Trân trọng,</div>
            <div><strong>Đội ngũ NovaShop</strong></div>
            <div class="muted">Email này được gửi tự động, vui lòng không trả lời trực tiếp.</div>
        </div>
    </div>
</body>
</html>

