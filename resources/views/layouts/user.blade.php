<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'NovaShop')</title>
<link rel="icon" href="{{ url('/favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ url('/favicon.ico') }}" type="image/x-icon">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { display: flex; flex-direction: column; min-height: 100vh; padding-top: 100px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
        .page-header h2 { margin: 0; font-size: 1.5rem; }
        .card { box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); border-radius: 0.75rem; overflow: hidden; }
        main .form-control { border-radius: 0.5rem; }
        .form-control:focus {
            outline: none !important;
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }
        main { flex: 1; }
        footer { margin-top: auto; }

        .alert-toast-container {
            position: fixed;
            top: 100px;
            left: 0;
            right: 0;
            z-index: 9999;
            padding: 1rem 15px;
            pointer-events: none;
        }
        .alert-toast-container .alert {
            pointer-events: auto;
            max-width: 720px;
            margin: 0 auto 0.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        main .btn-primary {
            background: #dc3545;
            border-color: #dc3545;
            border-radius: 0.5rem;
        }
        main .btn-primary:hover {
            background: #c82333;
            border-color: #bd2130;
        }

        header .navbar-shopee {
            background: #dc3545;
            padding: 0;
            flex-wrap: wrap;
        }
        .navbar-top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            padding: 0.35rem 0;
        }
        .navbar-top-row-left { flex-shrink: 0; }
        .navbar-top-row-right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
        }
        .navbar-top-row-left a,
        .navbar-top-row-right > a,
        .navbar-top-row-left a:hover,
        .navbar-top-row-right > a:hover,
        header .navbar-shopee .navbar-top-row-left a,
        header .navbar-shopee .navbar-top-row-right > a {
            color: #fff !important;
        }
        .navbar-top-row-left a:hover,
        .navbar-top-row-right > a:hover { opacity: 0.9; }
        .navbar-top-row span { color: #fff !important; }
        /* User menu dropdown - hiển thị khi hover */
        .user-menu-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            padding: 0.25rem 0 0.5rem 0;
        }
        .user-menu-wrap:hover .user-menu-dropdown { display: block; }
        .user-menu-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 0;
            padding-top: 12px;
            min-width: 180px;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1050;
            overflow: visible;
        }
        .user-menu-dropdown::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 11px solid transparent;
            border-right: 11px solid transparent;
            border-bottom: 11px solid #dee2e6;
        }
        .user-menu-dropdown::after {
            content: '';
            position: absolute;
            top: -9px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 10px solid transparent;
            border-right: 10px solid transparent;
            border-bottom: 10px solid #fff;
        }
        .user-menu-dropdown a {
            display: block;
            padding: 0.6rem 1rem;
            color: #212529 !important;
            text-decoration: none;
            font-size: 0.95rem;
            border: none;
            transition: background 0.15s;
        }
        .user-menu-dropdown a,
        .user-menu-dropdown a:visited,
        .navbar-top-row .user-menu-dropdown a {
            color: #212529 !important;
        }
        .user-menu-dropdown a:hover {
            background: #fff5f5;
            color: #dc3545 !important;
        }
        .user-menu-dropdown a:not(:last-child) {
            border-bottom: 1px solid #eee;
        }
        .navbar-top {
            width: 100%;
            padding: 0.5rem 0;
        }
        .navbar-top .navbar-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            width: 100%;
        }
        .navbar-top .navbar-brand-wrap {
            flex-shrink: 0;
        }
        .navbar-top .navbar-spacer {
            flex: 1;
            min-width: 0.5rem;
        }
        .navbar-top .navbar-search-wrap {
            flex-shrink: 0;
            width: 100%;
            max-width: 520px;
            margin: 0 0.5rem;
        }
        @media (max-width: 991px) {
            .navbar-top .navbar-row { flex-wrap: wrap; }
            .navbar-top .navbar-spacer { display: none; }
            .navbar-top .navbar-search-wrap { order: 3; width: 100%; max-width: none; margin: 0.5rem 0 0; }
        }
        .navbar-top .navbar-brand,
        .navbar-top .nav-link {
            color: #fff !important;
        }
        .navbar-brand-logo {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            color: #fff !important;
            font-size: 2.1rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            padding-top: 0.25rem;
        }
        .navbar-brand-logo:hover {
            color: #fff !important;
            text-decoration: none;
            opacity: 0.95;
        }
        .navbar-brand-logo .navbar-brand-icon {
            width: 2.6rem;
            height: 2.6rem;
            margin-right: 0.5rem;
            flex-shrink: 0;
        }
        .navbar-brand-logo .navbar-brand-icon svg {
            width: 100%;
            height: 100%;
            display: block;
        }
        .navbar-top .nav-link.active {
            font-weight: 600;
            text-decoration: underline;
        }
        .navbar-search-wrap.has-dropdown {
            position: relative;
        }
        .search-history-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            margin-top: 4px;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            max-height: 280px;
            overflow-y: auto;
            z-index: 1050;
            display: none;
        }
        .search-history-dropdown.show {
            display: block;
        }
        .search-history-dropdown .dropdown-title {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            color: #6c757d;
            border-bottom: 1px solid #dee2e6;
        }
        .search-history-dropdown .dropdown-item {
            display: block;
            width: 100%;
            padding: 0.6rem 1rem;
            color: #212529;
            text-align: left;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 0.95rem;
        }
        .search-history-dropdown .dropdown-item:hover {
            background: #f8f9fa;
        }
        .search-history-dropdown .history-item-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.6rem 1rem;
            color: #212529;
            font-size: 0.95rem;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }
        .search-history-dropdown .history-item-row:hover {
            background: #f8f9fa;
        }
        .search-history-dropdown .history-item-row .history-item-text {
            flex: 1;
            min-width: 0;
        }
        .search-history-dropdown .history-item-row .history-item-delete {
            color: #dc3545;
            font-size: 0.9rem;
            margin-left: 0.5rem;
            flex-shrink: 0;
            cursor: pointer;
        }
        .search-history-dropdown .history-item-row .history-item-delete:hover {
            text-decoration: underline;
        }
        .search-history-dropdown .dropdown-item-clear {
            color: #6c757d;
            font-size: 0.85rem;
            border-top: 1px solid #dee2e6;
        }

        .navbar-search-wrap {
            position: relative;
            width: 100%;
        }
        .navbar-search-wrap .form-control {
            border-radius: 999px;
            padding-left: 1.25rem;
            padding-right: 5rem;
            height: 44px;
            font-size: 1rem;
            border-color: #dee2e6;
        }
        .navbar-search-wrap .form-control:focus {
            outline: none !important;
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }
        .navbar-search-inner {
            display: flex;
            align-items: center;
            position: relative;
            border: 1px solid #dee2e6;
            border-radius: 999px;
            background: #fff;
            overflow: hidden;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .navbar-search-inner:focus-within {
            outline: none;
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }
        .navbar-search-inner .search-image-preview {
            display: flex;
            align-items: center;
            padding: 4px 0 4px 8px;
            flex-shrink: 0;
        }
        .navbar-search-inner .search-image-preview img {
            width: 36px;
            height: 36px;
            object-fit: cover;
            border-radius: 6px;
        }
        .navbar-search-inner .search-image-preview .search-image-clear {
            margin-left: 4px;
            padding: 2px 6px;
            border: none;
            background: #f8f9fa;
            color: #666;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            line-height: 1;
            text-decoration: none;
        }
        .navbar-search-inner .search-image-preview .search-image-clear:hover {
            background: #dc3545;
            color: #fff;
        }
        .navbar-search-inner .form-control {
            flex: 1;
            min-width: 0;
            border: none !important;
            border-radius: 0;
            padding-left: 0.75rem;
            padding-right: 5rem;
        }
        .navbar-search-inner .form-control:focus {
            box-shadow: none !important;
        }
        .navbar-search-wrap .btn-search-inside {
            position: absolute;
            right: 4px;
            top: 50%;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            padding: 0;
            border: none;
            background: transparent;
            color: #666;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .navbar-search-wrap .btn-search-inside:hover {
            background: rgba(0,0,0,0.06);
            color: #333;
        }
        .navbar-search-wrap .btn-search-inside svg {
            width: 18px;
            height: 18px;
        }
        .navbar-search-wrap .btn-search-image {
            right: 44px;
        }
        .navbar-cart-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            position: relative;
            flex-shrink: 0;
        }
        .navbar-cart-link:hover {
            color: #fff;
            background: rgba(255,255,255,0.15);
        }
        .navbar-cart-link svg {
            width: 24px;
            height: 24px;
        }
        .navbar-cart-badge {
            position: absolute;
            top: 4px;
            right: 4px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            font-size: 0.7rem;
            font-weight: 700;
            line-height: 18px;
            text-align: center;
            background: #fff;
            color: #dc3545;
            border-radius: 999px;
        }

        footer.bg-novashop {
            background: #c62828;
            color: #fff;
        }

        .btn-view-detail {
            padding: 0.6rem 1.5rem;
            border-radius: 999px;
            font-weight: 500;
        }
        .btn-primary.btn-view-detail {
            background: #dc3545;
            border-color: #dc3545;
        }
        .btn-primary.btn-view-detail:hover {
            background: #c82333;
            border-color: #bd2130;
        }
        .btn-outline-primary.btn-view-detail {
            color: #dc3545;
            border-color: #dc3545;
        }
        .btn-outline-primary.btn-view-detail:hover {
            background: #dc3545;
            color: #fff;
        }

        .product-card-actions {
            gap: 0.5rem;
            justify-content: space-between;
        }
        .product-card-add-form {
            min-width: 0;
            margin-left: auto;
        }
        .product-card-actions .btn-view-detail {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
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
        .product-card-price-old {
            font-size: 0.85rem;
            color: #888;
            text-decoration: line-through;
            margin-right: 0.35rem;
        }
        .product-card-price-new {
            font-size: 1.15rem;
            font-weight: 700;
            color: #dc3545;
        }
        /* Sidebar - danh mục bên trái */
        .products-with-sidebar {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }
        .products-sidebar {
            width: 220px;
            min-width: 220px;
            flex-shrink: 0;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0.0625rem 0.125rem rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .products-sidebar-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.9rem 1rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: #212529;
            background: #fff;
            border-bottom: 1px solid #eee;
        }
        .products-sidebar-title svg {
            width: 1.1rem;
            height: 1.1rem;
            color: #666;
        }
        .products-sidebar-title-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #212529;
            text-decoration: none;
        }
        .products-sidebar-title-link:hover {
            color: #dc3545;
        }
        .products-sidebar-list {
            padding: 0.5rem 0;
        }
        .products-sidebar-list a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.6rem 1rem;
            color: #212529;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.15s, color 0.15s;
        }
        .products-sidebar-list a:hover {
            background: #fff5f5;
            color: #dc3545;
        }
        .products-sidebar-list a.active {
            color: #dc3545;
            font-weight: 600;
        }
        .products-sidebar-list a.active::after {
            content: '';
            width: 0;
            height: 0;
            border-top: 5px solid transparent;
            border-bottom: 5px solid transparent;
            border-left: 5px solid #dc3545;
        }
        .products-sidebar-price {
            padding: 1rem;
            border-top: 1px solid #eee;
        }
        .products-sidebar-price-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0.75rem;
        }
        .products-sidebar-price-form {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .products-sidebar-price-inputs {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .products-sidebar-price-inputs .form-control {
            flex: 1;
            padding: 0.5rem 0.6rem;
            font-size: 0.9rem;
            border: 1px solid #dee2e6;
            border-radius: 6px;
        }
        .products-sidebar-price-inputs .form-control:focus {
            outline: none !important;
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }
        .products-sidebar-price-sep {
            color: #999;
            flex-shrink: 0;
        }
        .products-sidebar-price-btn {
            background: #dc3545;
            border-color: #dc3545;
            color: #fff;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 6px;
        }
        .products-sidebar-price-btn:hover {
            background: #c82333;
            border-color: #bd2130;
            color: #fff;
        }
        .products-main {
            flex: 1;
            min-width: 0;
        }
        .products-sort-bar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            padding: 0.75rem 0;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }
        .products-sort-bar .sort-label {
            font-size: 0.9rem;
            color: #666;
            margin-right: 0.25rem;
        }
        .products-sort-btn {
            padding: 0.4rem 0.9rem;
            font-size: 0.9rem;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            background: #fff;
            color: #212529;
            text-decoration: none;
            transition: all 0.15s;
        }
        .products-sort-btn:hover {
            border-color: #dc3545;
            color: #dc3545;
        }
        .products-sort-btn.active {
            background: #dc3545;
            border-color: #dc3545;
            color: #fff;
        }
        .products-sort-price {
            margin-left: auto;
            position: relative;
        }
        .products-sort-price select {
            padding: 0.4rem 1.75rem 0.4rem 0.75rem;
            font-size: 0.9rem;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            background: #fff;
            color: #212529;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
        }
        .products-sort-price select:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        @media (max-width: 767px) {
            .products-with-sidebar { flex-direction: column; }
            .products-sidebar { width: 100%; min-width: 0; }
        }
        /* Trang tất cả danh mục - lưới danh mục */
        .all-categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 1.5rem;
        }
        .all-categories-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #212529;
            padding: 1rem 0.5rem;
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            transition: box-shadow 0.2s, border-color 0.2s;
        }
        .all-categories-item:hover {
            border-color: #dc3545;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.15);
            color: #212529;
        }
        .all-categories-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
        }
        .all-categories-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .all-categories-icon-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #adb5bd;
        }
        .all-categories-name {
            font-size: 0.9rem;
            font-weight: 500;
            text-align: center;
            line-height: 1.3;
        }
        .breadcrumb-item + .breadcrumb-item::before { content: '›'; }
        .breadcrumb-item a { color: #dc3545; }
        .breadcrumb-item a:hover { text-decoration: underline; }
        /* Div bọc danh mục - nền trắng, mép trái/phải thẳng hàng với lưới sản phẩm (cùng canh với .row) */
        .categories-wrapper {
            background: #fff;
            margin-left: -15px;
            margin-right: -15px;
            padding: 1.25rem 15px;
            border-radius: 0.5rem;
            box-shadow: 0 0.0625rem 0.125rem rgba(0,0,0,0.05);
        }
        /* Danh mục (categories) - dưới header, trên sản phẩm */
        .categories-section {
            width: 100%;
        }
        .categories-section-title {
            font-size: 1rem;
            font-weight: 700;
            color: #212529;
            letter-spacing: 0.02em;
            text-align: center;
        }
        .categories-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 0.75rem;
            justify-content: space-between;
            width: 100%;
        }
        .category-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 7.5rem;
            min-height: 6.5rem;
            padding: 0.75rem 0.5rem;
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            color: #212529;
            text-decoration: none;
            text-align: center;
            transition: box-shadow 0.2s, border-color 0.2s;
        }
        .category-item:hover {
            border-color: #dc3545;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.15);
            color: #212529;
        }
        .category-item.active {
            border-color: #dc3545;
            background: #fff5f5;
            color: #c82333;
        }
        .category-item-icon {
            width: 2.75rem;
            height: 2.75rem;
            margin-bottom: 0.4rem;
            color: #6c757d;
        }
        .category-item.active .category-item-icon {
            color: #dc3545;
        }
        .category-item-icon svg {
            width: 100%;
            height: 100%;
            display: block;
        }
        .category-item-icon-img {
            overflow: hidden;
            border-radius: 4px;
        }
        .category-item-icon-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .category-item-icon-placeholder {
            display: block;
            width: 100%;
            height: 100%;
            background: #e9ecef;
            border-radius: 4px;
        }
        .category-item-name {
            font-size: 0.8rem;
            font-weight: 500;
            line-height: 1.2;
            word-break: break-word;
        }
        /* Phân trang: căn giữa, màu đỏ */
        main .pagination {
            justify-content: center;
            flex-wrap: wrap;
        }
        main .pagination .page-link {
            color: #dc3545;
            border-color: #dc3545;
            background: #fff;
        }
        main .pagination .page-link:hover {
            color: #fff;
            background: #dc3545;
            border-color: #dc3545;
        }
        main .pagination .page-item.active .page-link {
            background: #dc3545;
            border-color: #dc3545;
            color: #fff;
        }
        main .pagination .page-item.disabled .page-link {
            color: #dc3545;
            border-color: #dee2e6;
            background: #fff;
            opacity: 0.6;
        }
        main .pagination .page-link {
            padding: 0.5rem 0.85rem;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <header>
    <nav class="navbar navbar-shopee navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <div class="navbar-top-row d-none d-md-flex">
                <div class="navbar-top-row-left">
                    @auth
                    @if(auth()->user()->is_admin)
                    <a href="{{ route('admin.dashboard') }}">Quản trị</a>
                    @else
                    <a href="{{ url('/') }}">Danh mục sản phẩm</a>
                    @endif
                    @else
                    <a href="{{ url('/') }}">Danh mục sản phẩm</a>
                    @endauth
                </div>
                <div class="navbar-top-row-right">
                    @guest
                    <a href="{{ route('login') }}">Đăng nhập</a>
                    <a href="{{ route('register') }}">Đăng ký</a>
                    @else
                    <div class="user-menu-wrap">
                        @if(auth()->user()->avatar ?? null)
                            <img src="/images/avatars/{{ basename(auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}" class="rounded-circle" style="width: 28px; height: 28px; object-fit: cover; border: 2px solid rgba(255,255,255,0.8);">
                        @else
                            <span class="rounded-circle bg-white text-dark d-inline-flex align-items-center justify-content-center font-weight-bold" style="width: 28px; height: 28px; font-size: 0.8rem;">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
                        @endif
                        <span class="text-white">{{ auth()->user()->name }}</span>
                        <div class="user-menu-dropdown">
                            <a href="{{ route('profile') }}">Quản lý tài khoản</a>
                            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Đăng xuất</a>
                        </div>
                    </div>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
                    @endguest
                </div>
            </div>
        </div>
        <div class="navbar-top w-100">
            <div class="container">
                <div class="navbar-row">
                    <div class="navbar-brand-wrap">
                        <a class="navbar-brand navbar-brand-logo py-0 mb-0" href="{{ url('/') }}" title="Về trang chủ">
                            <span class="navbar-brand-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                            </span>
                            <span class="navbar-brand-text">NovaShop</span>
                        </a>
                    </div>
                    <div class="navbar-spacer" aria-hidden="true"></div>
                    <div class="navbar-search-wrap has-dropdown">
                        <form action="{{ route('search') }}" method="GET" id="search-form">
                            <div class="navbar-search-inner">
                                @php $searchImgUrl = session('searched_image_path') ? '/images/temp/' . basename(session('searched_image_path')) : null; @endphp
                                <div class="search-image-preview" id="search-image-preview" style="{{ $searchImgUrl ? 'display: flex;' : 'display: none;' }}">
                                    <img src="{{ $searchImgUrl ?? '' }}" alt="" id="search-image-preview-img">
                                    <a href="{{ route('search.clear.image') }}" class="search-image-clear" id="search-image-clear" aria-label="Xóa ảnh" title="Xóa ảnh">×</a>
                                </div>
                                <input type="text" name="q" id="search-input" class="form-control" placeholder="{{ $searchImgUrl ? 'Ảnh đã chọn' : 'Tìm sản phẩm hoặc chọn ảnh...' }}" value="{{ request('q') }}" aria-label="Tìm kiếm" autocomplete="off">
                                <button type="button" class="btn-search-inside btn-search-image" id="btn-search-by-image" aria-label="Tìm kiếm bằng hình ảnh" title="Tìm kiếm bằng hình ảnh">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                                </button>
                                <button type="submit" class="btn-search-inside" aria-label="Tìm kiếm">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                </button>
                            </div>
                        </form>
                        <form action="{{ route('search.by.image') }}" method="POST" enctype="multipart/form-data" id="search-by-image-form" class="d-none">
                            @csrf
                            <input type="file" name="image" id="search-image-input" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                        </form>
                        <div class="search-history-dropdown" id="search-history-dropdown" role="listbox">
                            <div class="dropdown-title">Lịch sử tìm kiếm</div>
                            <div id="search-history-list"></div>
                            <button type="button" class="dropdown-item dropdown-item-clear" id="search-history-clear">Xóa lịch sử</button>
                        </div>
                    </div>
                    <a href="{{ auth()->check() ? route('cart.index') : route('login') }}" class="navbar-cart-link" title="Giỏ hàng">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        @auth
                        @php $cartCount = auth()->user()->cart?->items()->sum('quantity') ?? 0; @endphp
                        @if($cartCount > 0)
                        <span class="navbar-cart-badge">{{ $cartCount > 99 ? '99+' : $cartCount }}</span>
                        @endif
                        @endauth
                    </a>
                    <div class="navbar-spacer d-none d-lg-block" aria-hidden="true"></div>
                </div>
            </div>
        </div>
    </nav>
    </header>

    @php
        $successMessage = session()->pull('success');
        $errorMessage = session()->pull('error');
    @endphp
    @if ($successMessage || $errorMessage)
    <div class="alert-toast-container">
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
    </div>
    @endif

    <main class="py-4">
        <div class="container">
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
    <script src="{{ asset('js/image-preview.js') }}"></script>
    <script>
        $(function() {
            setTimeout(function() { $('.alert').alert('close'); }, 3000);
        });
        (function() {
            var STORAGE_KEY = 'novashop_search_history';
            var MAX_ITEMS = 10;
            var input = document.getElementById('search-input');
            var form = document.getElementById('search-form');
            var dropdown = document.getElementById('search-history-dropdown');
            var listEl = document.getElementById('search-history-list');
            var clearBtn = document.getElementById('search-history-clear');
            var hideTimeout = null;

            function getHistory() {
                try {
                    var raw = localStorage.getItem(STORAGE_KEY);
                    return raw ? JSON.parse(raw) : [];
                } catch (e) { return []; }
            }
            function setHistory(arr) {
                try {
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(arr.slice(0, MAX_ITEMS)));
                } catch (e) {}
            }
            function addToHistory(q) {
                q = (q || '').trim();
                if (!q) return;
                var arr = getHistory();
                arr = arr.filter(function(item) { return item !== q; });
                arr.unshift(q);
                setHistory(arr);
            }
            function removeFromHistory(q) {
                var arr = getHistory().filter(function(item) { return item !== q; });
                setHistory(arr);
                renderHistory();
            }
            function renderHistory() {
                var arr = getHistory();
                listEl.innerHTML = '';
                if (arr.length === 0) {
                    listEl.innerHTML = '<div class="dropdown-item" style="color:#999;cursor:default;">Chưa có lịch sử</div>';
                    return;
                }
                arr.forEach(function(text) {
                    var row = document.createElement('button');
                    row.type = 'button';
                    row.className = 'history-item-row';
                    row.setAttribute('role', 'option');
                    var spanText = document.createElement('span');
                    spanText.className = 'history-item-text';
                    spanText.textContent = text;
                    var spanDelete = document.createElement('span');
                    spanDelete.className = 'history-item-delete';
                    spanDelete.textContent = 'Xóa';
                    spanDelete.setAttribute('aria-label', 'Xóa mục này');
                    row.appendChild(spanText);
                    row.appendChild(spanDelete);
                    row.addEventListener('mousedown', function(e) {
                        if (e.target === spanDelete || spanDelete.contains(e.target)) return;
                        e.preventDefault();
                        e.stopPropagation();
                        input.value = text;
                        dropdown.classList.remove('show');
                        window.location.href = form.action + '?q=' + encodeURIComponent(text);
                    });
                    spanDelete.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        removeFromHistory(text);
                    });
                    listEl.appendChild(row);
                });
            }
            function showDropdown() {
                clearTimeout(hideTimeout);
                renderHistory();
                if (getHistory().length > 0) {
                    dropdown.classList.add('show');
                } else {
                    dropdown.classList.remove('show');
                }
            }
            function hideDropdown() {
                hideTimeout = setTimeout(function() {
                    dropdown.classList.remove('show');
                }, 200);
            }

            if (input) {
                input.addEventListener('focus', showDropdown);
                input.addEventListener('blur', hideDropdown);
            }
            if (form) {
                form.addEventListener('submit', function() {
                    addToHistory(input.value);
                });
            }
            if (clearBtn) {
                clearBtn.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    setHistory([]);
                    renderHistory();
                });
            }
            if (dropdown) {
                dropdown.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                });
            }
            if (input && input.value.trim()) {
                addToHistory(input.value.trim());
            }

            var btnImageSearch = document.getElementById('btn-search-by-image');
            var imageForm = document.getElementById('search-by-image-form');
            var imageInput = document.getElementById('search-image-input');
            var imagePreview = document.getElementById('search-image-preview');
            var imagePreviewImg = document.getElementById('search-image-preview-img');
            var imageClearBtn = document.getElementById('search-image-clear');
            var searchInput = document.getElementById('search-input');
            var imageFile = null;

            function showImagePreview(file) {
                if (!file || !imagePreview || !imagePreviewImg) return;
                imageFile = file;
                var url = URL.createObjectURL(file);
                imagePreviewImg.src = url;
                imagePreview.style.display = 'flex';
                if (searchInput) searchInput.placeholder = 'Ảnh đã chọn';
            }

            if (btnImageSearch && imageForm && imageInput) {
                btnImageSearch.addEventListener('click', function() {
                    imageInput.click();
                });
                imageInput.addEventListener('change', function() {
                    if (this.files && this.files.length > 0) {
                        showImagePreview(this.files[0]);
                        imageForm.submit();
                    }
                });
            }
            if (imageClearBtn) {
                imageClearBtn.addEventListener('click', function(e) {
                    if (imageFile) {
                        e.preventDefault();
                        imageFile = null;
                        if (imagePreviewImg && imagePreviewImg.src && imagePreviewImg.src.startsWith('blob:')) {
                            URL.revokeObjectURL(imagePreviewImg.src);
                        }
                        if (imagePreviewImg) imagePreviewImg.src = '';
                        if (imagePreview) imagePreview.style.display = 'none';
                        if (searchInput) searchInput.placeholder = 'Tìm sản phẩm hoặc chọn ảnh...';
                        if (imageInput) imageInput.value = '';
                    }
                });
            }
        })();
    </script>
    @stack('scripts')
</body>
</html>
