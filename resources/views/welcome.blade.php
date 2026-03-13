@extends('layouts.user')

@section('title', 'NovaShop')

@section('content')
@if(isset($q) && $q !== '')
<div class="mb-3">
    <p class="text-muted mb-0">Kết quả tìm kiếm: <strong>{{ $q }}</strong></p>
</div>
@endif

{{-- Danh mục --}}
@if(isset($categories) && $categories->isNotEmpty())
<div class="categories-wrapper mb-4">
    <section class="categories-section">
        <h2 class="categories-section-title mb-3">DANH MỤC</h2>
        <div class="categories-grid">
        @foreach($categories as $cat)
        <a href="{{ isset($q) ? route('search', array_filter(['q' => $q, 'category_id' => $cat->id])) : route('category.products', $cat) }}" class="category-item {{ (isset($category) && (int)$category->id === (int)$cat->id) || (isset($categoryId) && (int)$categoryId === (int)$cat->id) ? 'active' : '' }}">
            <span class="category-item-icon category-item-icon-img">
                @if($cat->image)
                    <img src="/images/categories/{{ basename($cat->image) }}" alt="{{ $cat->name }}" loading="lazy">
                @else
                    <span class="category-item-icon-placeholder" aria-hidden="true"></span>
                @endif
            </span>
            <span class="category-item-name">{{ $cat->name }}</span>
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
                <a href="{{ route('products.show', $product) }}" class="btn btn-primary btn-view-detail mt-auto">Xem chi tiết</a>
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

@if($products->hasPages())
<div class="d-flex justify-content-center mt-4">
    @php
        $paginator = $products;
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $elements = [];
        if ($last <= 6) {
            for ($i = 1; $i <= $last; $i++) { $elements[] = $i; }
        } else {
            $start = max(1, min($current - 2, $last - 5));
            $elements = [$start, $start + 1, $start + 2, '...', $start + 3, $start + 4, $start + 5];
        }
    @endphp
    <nav>
        <ul class="pagination">
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                @if($paginator->onFirstPage())
                    <span class="page-link">&lsaquo;</span>
                @else
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}">&lsaquo;</a>
                @endif
            </li>
            @foreach($elements as $el)
                @if($el === '...')
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                @else
                    <li class="page-item {{ (int)$el === (int)$current ? 'active' : '' }}">
                        @if((int)$el === (int)$current)
                            <span class="page-link">{{ $el }}</span>
                        @else
                            <a class="page-link" href="{{ $paginator->url($el) }}">{{ $el }}</a>
                        @endif
                    </li>
                @endif
            @endforeach
            <li class="page-item {{ !$paginator->hasMorePages() ? 'disabled' : '' }}">
                @if(!$paginator->hasMorePages())
                    <span class="page-link">&rsaquo;</span>
                @else
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}">&rsaquo;</a>
                @endif
            </li>
        </ul>
    </nav>
</div>
@endif

@if(isset($suggestedProducts) && $suggestedProducts->isNotEmpty())
<section class="suggested-today mt-5 pt-4 border-top">
    <h2 class="text-center mb-4 font-weight-bold" style="font-size: 1.25rem; color: #333;">Gợi ý hôm nay</h2>
    <div class="row">
        @foreach($suggestedProducts as $product)
        <div class="col-6 col-md-4 col-lg-3 mb-4">
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
                    <a href="{{ route('products.show', $product) }}" class="btn btn-primary btn-view-detail mt-auto">Xem chi tiết</a>
                    @else
                    <a href="{{ route('login') }}" class="btn btn-outline-primary btn-view-detail mt-auto">Đăng nhập để xem</a>
                    @endauth
                </div>
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif
@endsection
