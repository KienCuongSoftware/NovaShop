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
        .alert-toast-container {
            position: fixed;
            top: 1rem;
            left: 270px;
            right: 1rem;
            z-index: 9999;
            pointer-events: none;
        }
        .alert-toast-container .alert {
            pointer-events: auto;
            max-width: 600px;
            margin-bottom: 0.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .admin-main .card { box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
        .page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
        .page-header h2 { margin: 0; font-size: 1.5rem; }
        .page-header .admin-toolbar { display: flex; align-items: center; flex-wrap: wrap; gap: 0.75rem; }
        .page-header .admin-search-form .input-group { border-radius: 0.5rem; overflow: hidden; }
        .page-header .admin-search-form .form-control { border-radius: 0.5rem 0 0 0.5rem; border-right: 0; }
        .page-header .admin-search-form .input-group-append .btn { border-radius: 0 0.5rem 0.5rem 0; background: #dc3545; border-color: #dc3545; color: #fff; }
        .page-header .admin-search-form .input-group-append .btn:hover { background: #c82333; border-color: #bd2130; color: #fff; }
        .page-header .admin-toolbar .btn-success { border-radius: 0.5rem; }
        /* Phần tìm kiếm trong toàn bộ trang admin: bo góc + nút đỏ */
        .admin-main .admin-search-form .form-control,
        .admin-main .admin-search-form input.form-control { border-radius: 0.5rem; }
        .admin-main .admin-search-form .input-group .form-control:first-child { border-radius: 0.5rem 0 0 0.5rem; }
        .admin-main .admin-search-form .input-group-append .btn,
        .admin-main .admin-search-form button[type="submit"].btn {
            border-radius: 0 0.5rem 0.5rem 0;
            background: #dc3545;
            border-color: #dc3545;
            color: #fff;
        }
        .admin-main .admin-search-form .input-group-append .btn:hover,
        .admin-main .admin-search-form button[type="submit"].btn:hover {
            background: #c82333;
            border-color: #bd2130;
            color: #fff;
        }
        .admin-main .admin-search-form.d-flex .form-control { border-radius: 0.5rem; }
        .admin-main .admin-search-form.d-flex button[type="submit"].btn {
            border-radius: 0.5rem;
            background: #dc3545;
            border-color: #dc3545;
            color: #fff;
        }
        .admin-main .admin-search-form.d-flex button[type="submit"].btn:hover {
            background: #c82333;
            border-color: #bd2130;
            color: #fff;
        }
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
                <a class="nav-link {{ request()->routeIs('admin.brands.*') ? 'active' : '' }}" href="{{ route('admin.brands.index') }}">
                    Thương hiệu
                </a>
                <a class="nav-link {{ request()->routeIs('admin.attributes.*') ? 'active' : '' }}" href="{{ route('admin.attributes.index') }}">
                    Thuộc tính
                </a>
                <a class="nav-link {{ request()->routeIs('admin.flash-sales.*') ? 'active' : '' }}" href="{{ route('admin.flash-sales.index') }}">
                    Flash Sale
                </a>
                <a class="nav-link {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}" href="{{ route('admin.coupons.index') }}">
                    Mã giảm giá
                </a>
                <a class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
                    Đơn hàng
                </a>
                <a class="nav-link {{ request()->routeIs('admin.inventory-logs.*') ? 'active' : '' }}" href="{{ route('admin.inventory-logs.index') }}">
                    Nhập/xuất kho
                </a>
                <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                    Người dùng
                </a>
                <div class="nav-divider"></div>
                <a class="nav-link {{ request()->routeIs('admin.profile.*') ? 'active' : '' }}" href="{{ route('admin.profile.edit') }}">
                    Thông tin tài khoản
                </a>
                <a class="nav-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();">
                    Đăng xuất
                </a>
                <form id="admin-logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </nav>
        </aside>

        @php
            $successMessage = session()->pull('success');
            $errorMessage = session()->pull('error');
            $hasValidationErrors = $errors->any();
        @endphp
        @if ($successMessage || $errorMessage || $hasValidationErrors)
        <div class="alert-toast-container">
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
            @if ($hasValidationErrors)
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                </div>
            @endif
        </div>
        @endif

        <!-- Nội dung chính - chỉ phần này đổi khi chuyển trang -->
        <main class="admin-main">
            @yield('content')
        </main>
    </div>

    <div class="modal fade" id="globalConfirmModal" tabindex="-1" aria-labelledby="globalConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="globalConfirmModalLabel">Xác nhận</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="globalConfirmModalBody">Bạn có chắc muốn thực hiện thao tác này?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger" id="globalConfirmModalOk">Đồng ý</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script src="{{ asset('js/image-preview.js') }}"></script>
    <script>
        $(function() { setTimeout(function() { $('.alert').alert('close'); }, 3000); });
        (function() {
            var pendingResolve = null;
            function cleanupResolve(value) {
                if (pendingResolve) {
                    pendingResolve(value);
                    pendingResolve = null;
                }
            }
            window.bsConfirm = function(message) {
                return new Promise(function(resolve) {
                    var modal = document.getElementById('globalConfirmModal');
                    var body = document.getElementById('globalConfirmModalBody');
                    var okBtn = document.getElementById('globalConfirmModalOk');
                    if (!modal || !body || !okBtn || typeof $ === 'undefined' || !$.fn.modal) {
                        resolve(window.confirm(message || 'Bạn có chắc muốn thực hiện thao tác này?'));
                        return;
                    }
                    body.textContent = message || 'Bạn có chắc muốn thực hiện thao tác này?';
                    pendingResolve = resolve;
                    okBtn.onclick = function() {
                        cleanupResolve(true);
                        $('#globalConfirmModal').modal('hide');
                    };
                    $('#globalConfirmModal')
                        .off('hidden.bs.modal.globalConfirm')
                        .on('hidden.bs.modal.globalConfirm', function() {
                            cleanupResolve(false);
                        })
                        .modal('show');
                });
            };
            window.bsConfirmSubmit = function(formEl, message) {
                if (!formEl) return false;
                window.bsConfirm(message).then(function(ok) {
                    if (ok) formEl.submit();
                });
                return false;
            };
        })();
    </script>
    @stack('scripts')
</body>
</html>
