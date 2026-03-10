<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'NovaShop')</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { display: flex; flex-direction: column; min-height: 100vh; padding-top: 56px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
        .page-header h2 { margin: 0; font-size: 1.5rem; }
        .card { box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); border-radius: 0.75rem; overflow: hidden; }
        main .form-control { border-radius: 0.5rem; }
        main { flex: 1; }
        footer { margin-top: auto; }

        main .btn-primary {
            background: #bf8058;
            border-color: #bf8058;
            border-radius: 0.5rem;
        }
        main .btn-primary:hover {
            background: #a5663f;
            border-color: #a5663f;
        }

        header .navbar {
            background: linear-gradient(90deg, #bf8058, #8d6e63); /* nâu nhạt hơn */
        }
        header .navbar .navbar-brand,
        header .navbar .nav-link {
            color: #fff !important;
        }
        header .navbar .nav-link.active {
            font-weight: 600;
            text-decoration: underline;
        }

        footer.bg-novashop {
            background: #8d6e63;
            color: #fff;
        }

        .btn-view-detail {
            padding: 0.6rem 1.5rem;
            border-radius: 999px;
            font-weight: 500;
        }
        .btn-primary.btn-view-detail {
            background: #bf8058;
            border-color: #bf8058;
        }
        .btn-primary.btn-view-detail:hover {
            background: #a5663f;
            border-color: #a5663f;
        }
        .btn-outline-primary.btn-view-detail {
            color: #bf8058;
            border-color: #bf8058;
        }
        .btn-outline-primary.btn-view-detail:hover {
            background: #bf8058;
            color: #fff;
        }

        .product-card-img {
            height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f8f9fa;
        }
        .product-card-img img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }
        .product-card-content {
            margin-bottom: 0.35rem;
        }
        .product-card-title {
            height: 2.6em;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.3;
            margin-bottom: 0.4rem;
            font-size: 1rem;
            font-weight: 700;
            color: #0d0d0d;
        }
        .product-card-desc {
            height: 4.05em;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            line-height: 1.35;
            margin-bottom: 0;
            font-weight: 500;
            color: #5c5c5c;
        }
        .product-card-category {
            font-weight: 500;
            color: #0d0d0d;
        }
        .product-card-price {
            font-weight: 700;
            color: #4a3428;
        }
    </style>
</head>
<body>
    <header>
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
        <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">NovaShop</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item {{ request()->routeIs('welcome') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ url('/') }}">Trang chủ</a>
                </li>
                @guest
                <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">Đăng ký</a>
                </li>
                <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Đăng nhập</a>
                </li>
                @else
                @if(auth()->user()->is_admin ?? false)
                <li class="nav-item">
                            <a class="nav-link" href="{{ route('admin.dashboard') }}">Quản trị</a>
                </li>
                @else
                <li class="nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('products.index') }}">Sản phẩm</a>
                </li>
                @endif
                <li class="nav-item">
                            <a class="nav-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Đăng xuất</a>
                </li>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
                @endguest
            </ul>
        </div>
        </div>
    </nav>
    </header>

    <main class="py-4">
        <div class="container">
            @php
                $successMessage = session()->pull('success');
                $errorMessage = session()->pull('error');
            @endphp
            @if ($successMessage)
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ $successMessage }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            @endif
            @if ($errorMessage)
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ $errorMessage }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            @endif
            @yield('content')
        </div>
    </main>

    <footer class="bg-novashop py-4 mt-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6 text-center text-md-left">
                    <a href="{{ url('/') }}" class="text-white">NovaShop</a>
                </div>
                <div class="col-md-6 text-center text-md-right small">
                    &copy; {{ date('Y') }} NovaShop. Tất cả quyền được bảo lưu.
                </div>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script>
        $(function() {
            setTimeout(function() { $('.alert').alert('close'); }, 3000);
        });
    </script>
    @stack('scripts')
</body>
</html>
