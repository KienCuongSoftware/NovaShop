<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Trang quản trị') - NovaShop</title>
<link rel="icon" href="{{ url('/favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ url('/favicon.ico') }}" type="image/x-icon">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { overflow-x: hidden; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        .admin-sidebar {
            width: 250px;
            min-width: 250px;
            background: linear-gradient(180deg, #c62828, #b71c1c);
            color: #fff;
            flex-shrink: 0;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            overflow-y: auto;
        }
        .admin-sidebar .brand {
            padding: 1.25rem;
            font-size: 1.25rem;
            font-weight: 600;
            border-bottom: 1px solid rgba(255,255,255,0.15);
        }
        .admin-sidebar .brand a { color: #fff; text-decoration: none; }
        .admin-sidebar .brand a:hover { color: #fff; opacity: 0.9; }
        .admin-sidebar .nav { flex-direction: column; padding: 1rem 0; }
        .admin-sidebar .nav-link {
            color: rgba(255,255,255,0.9);
            padding: 0.6rem 1.25rem;
            border-left: 3px solid transparent;
        }
        .admin-sidebar .nav-link:hover { color: #fff; background: rgba(255,255,255,0.1); }
        .admin-sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.15);
            border-left-color: #fff;
        }
        .admin-sidebar .nav-divider {
            height: 1px;
            margin: 0.5rem 1rem;
            background: rgba(255,255,255,0.15);
        }
        .admin-main {
            flex: 1;
            margin-left: 250px;
            padding: 1.5rem 2rem;
            background: #ffffff;
            min-height: 100vh;
        }
        .admin-main .card { box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
        .page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
        .page-header h2 { margin: 0; font-size: 1.5rem; }
        /* Phân trang: căn giữa, màu đỏ */
        .admin-main .card-footer {
            display: flex;
            justify-content: center;
        }
        .admin-main .pagination {
            justify-content: center;
            flex-wrap: wrap;
        }
        .admin-main .pagination .page-link {
            color: #dc3545;
            border-color: #dc3545;
            background: #fff;
        }
        .admin-main .pagination .page-link:hover {
            color: #fff;
            background: #dc3545;
            border-color: #dc3545;
        }
        .admin-main .pagination .page-item.active .page-link {
            background: #dc3545;
            border-color: #dc3545;
            color: #fff;
        }
        .admin-main .pagination .page-item.disabled .page-link {
            color: #dc3545;
            border-color: #dee2e6;
            background: #fff;
            opacity: 0.6;
        }
        .admin-main .pagination .page-link {
            padding: 0.5rem 0.85rem;
            font-size: 1rem;
        }
        .admin-main .badge-role {
            min-width: 4.5rem;
            padding: 0.45rem 0.75rem;
            font-size: 0.95rem;
            display: inline-block;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Navbar bên trái - cố định, không đổi khi chuyển trang -->
        <aside class="admin-sidebar">
            <div class="brand">
                <a href="{{ route('admin.dashboard') }}">NovaShop - Quản trị</a>
            </div>
            <nav class="nav">
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    Trang quản trị
                </a>
                <a class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
                    Sản phẩm
                </a>
                <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
                    Danh mục
                </a>
                <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                    Người dùng
                </a>
                <div class="nav-divider"></div>
                <a class="nav-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();">
                    Đăng xuất
                </a>
                <form id="admin-logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </nav>
        </aside>

        <!-- Nội dung chính - chỉ phần này đổi khi chuyển trang -->
        <main class="admin-main">
            @php
                $successMessage = session()->pull('success');
                $errorMessage = session()->pull('error');
            @endphp
            @if ($successMessage)
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ $successMessage }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                </div>
            @endif
            @if ($errorMessage)
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ $errorMessage }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script>
        $(function() { setTimeout(function() { $('.alert').alert('close'); }, 3000); });
    </script>
    @stack('scripts')
</body>
</html>
