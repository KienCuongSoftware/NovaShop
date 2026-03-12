@extends('layouts.user')

@section('title', 'NovaShop')

@section('content')
<div class="mb-4">
    <h1 class="mb-4">Chào mừng bạn đến với NovaShop</h1>
    @if(isset($q) && $q !== '')
        <p class="text-muted">Kết quả tìm kiếm: <strong>{{ $q }}</strong></p>
    @endif
</div>

{{-- Danh mục (dưới header, trên danh sách sản phẩm) - div nền trắng thẳng hàng với lưới sản phẩm --}}
@if(isset($categories) && $categories->isNotEmpty())
<div class="categories-wrapper mb-4">
    <section class="categories-section">
        <h2 class="categories-section-title mb-3">DANH MỤC</h2>
        <div class="categories-grid">
        <a href="{{ isset($q) ? route('search', array_filter(['q' => $q])) : url('/') }}" class="category-item {{ !isset($categoryId) || $categoryId === null ? 'active' : '' }}">
            <span class="category-item-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            </span>
            <span class="category-item-name">Tất cả</span>
        </a>
        @foreach($categories as $category)
        <a href="{{ isset($q) ? route('search', array_filter(['q' => $q, 'category_id' => $category->id])) : url('/?category_id=' . $category->id) }}" class="category-item {{ (isset($categoryId) && (int)$categoryId === (int)$category->id) ? 'active' : '' }}">
            <span class="category-item-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
            </span>
            <span class="category-item-name">{{ $category->name }}</span>
        </a>
        @endforeach
        </div>
    </section>
</div>
@endif

<div class="row">
    @forelse ($products as $product)
    <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
        <div class="card h-100 product-card">
            <div class="card-body text-left d-flex flex-column">
                <div class="product-card-img mb-2">
                    @if($product->image)
                        <img src="/images/products/{{ basename($product->image) }}" alt="{{ $product->name }}" class="img-fluid" loading="lazy">
                    @else
                        <div class="bg-light h-100 d-flex align-items-center justify-content-center text-muted small">Không có ảnh</div>
                    @endif
                </div>
                <div class="product-card-content">
                    <h5 class="card-title product-card-title">{{ $product->name }}</h5>
                    <p class="card-text small product-card-desc">{{ Str::limit($product->description, 80) }}</p>
                </div>
                <p class="card-text mb-1">
                    @if($product->old_price !== null)
                        <span class="product-card-price-old">{{ number_format($product->old_price, 0, ',', '.') }}₫</span>
                    @endif
                    <span class="product-card-price-new">{{ number_format($product->price, 0, ',', '.') }}₫</span>
                </p>
                <p class="card-text small product-card-category mb-2">Danh mục: {{ optional($product->category)->name }}</p>
                @auth
                <a href="{{ route('products.show', $product->id) }}" class="btn btn-primary btn-view-detail mt-auto">Xem chi tiết</a>
                @else
                <a href="{{ route('login') }}" class="btn btn-outline-primary btn-view-detail mt-auto">Đăng nhập để xem</a>
                @endauth
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <p class="text-muted">Chưa có sản phẩm nào.</p>
    </div>
    @endforelse
</div>

<div class="d-flex justify-content-center mt-4">
    {{ $products->links() }}
</div>
@endsection
