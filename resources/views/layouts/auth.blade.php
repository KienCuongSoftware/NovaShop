<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'NovaShop - Tài khoản')</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f7eee7; /* nâu nhạt */
        }
        .auth-wrapper {
            width: 100%;
            max-width: 460px;
            padding: 1.5rem;
        }
        .auth-card {
            border-radius: 0.9rem;
            box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.1);
            overflow: hidden;
            background: #ffffff;
        }
        .auth-header {
            background: linear-gradient(90deg, #bf8058, #8d6e63); /* nâu nhạt hơn */
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
            border-color: #bf8058;
            box-shadow: 0 0 0 0.2rem rgba(191, 128, 88, 0.25);
            outline: 0;
        }
        .btn-auth-primary {
            background: #bf8058;
            border-color: #bf8058;
            border-radius: 0.6rem;
            font-weight: 500;
            color: #fff;
        }
        .btn-auth-primary:hover {
            background: #a5663f;
            border-color: #a5663f;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header d-flex justify-content-between align-items-center">
                <span class="auth-logo">NovaShop</span>
                <small>@yield('title')</small>
            </div>
            <div class="auth-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Đóng">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
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

