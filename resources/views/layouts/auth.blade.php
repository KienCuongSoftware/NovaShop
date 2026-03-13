<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'NovaShop - Tài khoản')</title>
<link rel="icon" href="{{ url('/favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ url('/favicon.ico') }}" type="image/x-icon">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff5f5;
        }
        .auth-wrapper {
            width: 100%;
            max-width: 460px;
            padding: 1.5rem;
        }
        .auth-card {
            border-radius: 0.9rem;
            box-shadow: 0 0.5rem 1.5rem rgba(220, 53, 69, 0.15);
            overflow: hidden;
            background: #ffffff;
        }
        .auth-header {
            background: linear-gradient(90deg, #dc3545, #c62828);
            color: #fff;
            padding: 1rem 1.5rem;
            font-weight: 600;
        }
        .auth-body {
            padding: 1.5rem;
        }
        .auth-title {
            margin-bottom: 1.25rem;
        }
        .auth-logo {
            font-weight: 600;
            letter-spacing: 0.03em;
        }
        .form-control {
            border-radius: 0.5rem;
        }
        .form-control:hover,
        .form-control:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
            outline: 0;
        }
        .btn-auth-primary {
            background: #dc3545;
            border-color: #dc3545;
            border-radius: 0.6rem;
            font-weight: 500;
            color: #fff;
        }
        .btn-auth-primary:hover {
            background: #c82333;
            border-color: #bd2130;
            color: #fff;
        }
        .alert-toast-container {
            position: fixed;
            top: 1rem;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 460px;
            padding: 0 1.5rem;
            z-index: 9999;
            pointer-events: none;
        }
        .alert-toast-container .alert {
            pointer-events: auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    @php
        $authSuccess = session()->pull('success');
        $authErrors = $errors->any();
    @endphp
    @if ($authSuccess || $authErrors)
    <div class="alert-toast-container">
        @if ($authSuccess)
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ $authSuccess }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
        @if ($authErrors)
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0 pl-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
    </div>
    @endif

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header d-flex justify-content-between align-items-center">
                <span class="auth-logo">NovaShop</span>
                <small>@yield('title')</small>
            </div>
            <div class="auth-body">
                @yield('content')
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script>
        $(function() { setTimeout(function() { $('.alert').alert('close'); }, 3000); });
    </script>
</body>
</html>

