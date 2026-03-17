@extends('layouts.user')

@section('title', 'NovaShop')

@section('containerClass')
container{{ ($showSidebarAndFilter ?? false) ? ' products-container-wide' : ' products-container-narrow' }}
@endsection

@section('content')
@php
    $currentSort = $currentSort ?? 'popular';
    $priceMin = $priceMin ?? null;
    $priceMax = $priceMax ?? null;
    $baseParams = array_filter([
        'sort' => $currentSort,
        'price_min' => $priceMin,
        'price_max' => $priceMax,
        'brand' => $brandSlug ?? null,
    ]);
    $sortBaseUrl = isset($category)
        ? route('category.products', array_filter(['category' => $category, 'brand' => $brandSlug ?? null]))
        : (isset($q) ? route('search', array_filter(['q' => $q, 'category_id' => $categoryId ?? null])) : route('welcome'));
    $sortSep = str_contains($sortBaseUrl, '?') ? '&' : '?';
    $priceParams = array_filter(['price_min' => $priceMin, 'price_max' => $priceMax, 'brand' => $brandSlug ?? null]);
    $priceFormAction = isset($category)
        ? route('category.products', array_filter(['category' => $category, 'brand' => $brandSlug ?? null]))
        : (isset($q) ? route('search', array_filter(['q' => $q, 'category_id' => $categoryId ?? null])) : route('welcome'));
@endphp

@if(isset($q) && $q !== '')
<div class="mb-3">
    <p class="text-muted mb-0">Kết quả tìm kiếm: <strong>{{ $q }}</strong></p>
</div>
@endif
@if(!($showSidebarAndFilter ?? false) && isset($categories) && $categories->isNotEmpty())
<section class="home-categories-section mb-4">
    <h2 class="home-categories-title mb-3 font-weight-bold">DANH MỤC</h2>
    <div class="home-categories-grid">
        @foreach($categories as $cat)
        <a href="{{ route('category.products', $cat) }}" class="home-categories-item">
            <div class="home-categories-icon">
                @if($cat->image)
                    <img src="/images/categories/{{ basename($cat->image) }}" alt="{{ $cat->name }}" loading="lazy">
                @else
                    <span class="home-categories-icon-placeholder">{{ Str::limit($cat->name, 1) }}</span>
                @endif
            </div>
            <span class="home-categories-name">{{ $cat->name }}</span>
        </a>
        @endforeach
    </div>
</section>
@endif

<div class="products-with-sidebar {{ ($showSidebarAndFilter ?? false) ? '' : 'products-no-sidebar' }}">
    @if($showSidebarAndFilter ?? false)
    {{-- Sidebar: Khi xem category cụ thể chỉ hiển thị danh mục con của nó --}}
    <aside class="products-sidebar">
        <h2 class="products-sidebar-title">
            @if(isset($category))
                <a href="{{ $category->parent_id ? route('category.products', $category->parent) : route('all.categories') }}" class="products-sidebar-title-link">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    {{ $category->parent_id ? $category->parent->name : 'Tất cả danh mục' }}
                </a>
            @else
                <a href="{{ route('all.categories') }}" class="products-sidebar-title-link">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    Tất cả danh mục
                </a>
            @endif
        </h2>
        <nav class="products-sidebar-list">
            @if(isset($category))
                {{-- Đang xem category: luôn hiển thị danh mục anh em (siblings) để dễ chuyển đổi --}}
                @php
                    $sidebarItems = $category->parent_id
                        ? $category->parent->children
                        : collect([$category])->merge($category->children);
                @endphp
                @if($sidebarItems->isNotEmpty())
                    <div class="sidebar-categories-wrap" id="sidebar-categories-wrap">
                        @foreach($sidebarItems as $item)
                            @php
                                $isItemActive = $item->id === $category->id || in_array($item->id, $activeCategoryIds ?? []);
                                $itemUrl = isset($q) ? route('search', array_filter(['q' => $q ?? '', 'category_id' => $item->id])) : route('category.products', $item);
                            @endphp
                            <a href="{{ $itemUrl }}" class="sidebar-cat-link {{ $isItemActive ? 'active' : '' }}" style="padding-left: 12px;">{{ $item->name }}</a>
                        @endforeach
                        @if($sidebarItems->count() > 3)
                            <button type="button" class="btn btn-link btn-sm p-0 mt-1 sidebar-toggle-more" data-toggle-wrap="sidebar-categories-wrap" data-more-text="Xem thêm" data-less-text="Thu gọn"><span class="toggle-label">Xem thêm</span></button>
                        @endif
                    </div>
                @else
                    <span class="text-muted small" style="padding-left: 12px;">Không có danh mục con</span>
                @endif
            @else
                {{-- Trạng thái tìm kiếm hoặc không chọn category: hiển thị toàn bộ danh mục cha và danh mục con --}}
                @if(isset($categories) && $categories->isNotEmpty())
                @foreach($categories as $cat)
                    @include('partials.category-tree-item', ['category' => $cat, 'level' => 0])
                @endforeach
                @endif
            @endif
        </nav>

        {{-- Thương hiệu (chỉ khi xem category) --}}
        @if(isset($category) && ($categoryBrands ?? collect())->isNotEmpty())
        <div class="products-sidebar-brands">
            <h3 class="products-sidebar-price-title mb-2">Thương hiệu</h3>
            <div class="products-sidebar-brands-list sidebar-brands-wrap" id="sidebar-brands-wrap">
                <a href="{{ route('category.products', $category) }}{{ ($priceMin || $priceMax) ? '?' . http_build_query(array_filter(['price_min' => $priceMin, 'price_max' => $priceMax])) : '' }}" class="products-sidebar-brand-item {{ !($brandSlug ?? null) ? 'active' : '' }}">
                    <span class="brand-check">{{ !($brandSlug ?? null) ? '✓' : '' }}</span>
                    <span>Tất cả</span>
                </a>
                @foreach($categoryBrands as $b)
                <a href="{{ route('category.products', array_filter(['category' => $category, 'brand' => $b->slug, 'price_min' => $priceMin, 'price_max' => $priceMax])) }}" class="products-sidebar-brand-item sidebar-brand-link {{ ($brandSlug ?? null) === $b->slug ? 'active' : '' }}">
                    <span class="brand-check">{{ ($brandSlug ?? null) === $b->slug ? '✓' : '' }}</span>
                    @if($b->logo)
                    <img src="/images/brands/{{ basename($b->logo) }}" alt="{{ $b->name }}" class="brand-logo-thumb">
                    @endif
                    <span>{{ $b->name }}</span>
                </a>
                @endforeach
            </div>
            @if($categoryBrands->count() > 3)
                <button type="button" class="btn btn-link btn-sm p-0 mt-1 sidebar-toggle-more" data-toggle-wrap="sidebar-brands-wrap" data-more-text="Xem thêm" data-less-text="Thu gọn"><span class="toggle-label">Xem thêm</span></button>
            @endif
        </div>
        @endif

        {{-- Khoảng giá --}}
        <div class="products-sidebar-price">
            <h3 class="products-sidebar-price-title">Khoảng giá</h3>
            <form method="GET" action="{{ $priceFormAction }}" class="products-sidebar-price-form">
                @if(isset($q))<input type="hidden" name="q" value="{{ $q }}">@endif
                @if(isset($categoryId) && $categoryId)<input type="hidden" name="category_id" value="{{ $categoryId }}">@endif
                @if(isset($category) && ($brandSlug ?? null))<input type="hidden" name="brand" value="{{ $brandSlug }}">@endif
                <input type="hidden" name="sort" value="{{ $currentSort }}">
                <div class="products-sidebar-price-inputs">
                    <input type="number" name="price_min" class="form-control" placeholder="₫ TỪ" min="0" step="1000" value="{{ $priceMin }}">
                    <span class="products-sidebar-price-sep">-</span>
                    <input type="number" name="price_max" class="form-control" placeholder="₫ ĐẾN" min="0" step="1000" value="{{ $priceMax }}">
                </div>
                <button type="submit" class="btn btn-block products-sidebar-price-btn">ÁP DỤNG</button>
            </form>
        </div>
    </aside>
    @endif

    <div class="products-main">
        {{-- Logo thương hiệu đầu trang (khi xem category) --}}
        @if(isset($category) && ($categoryBrands ?? collect())->isNotEmpty())
        <section class="brands-section mb-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h3 class="brands-section-title mb-0">Thương hiệu</h3>
            </div>
            <div class="brands-grid">
                @foreach($categoryBrands as $b)
                <a href="{{ route('category.products', array_filter(['category' => $category, 'brand' => $b->slug, 'price_min' => $priceMin, 'price_max' => $priceMax])) }}" class="brands-grid-item {{ ($brandSlug ?? null) === $b->slug ? 'active' : '' }}">
                    @if($b->logo)
                    <img src="/images/brands/{{ basename($b->logo) }}" alt="{{ $b->name }}" class="brands-grid-logo" loading="lazy">
                    @endif
                    <span class="brands-grid-name">{{ $b->name }}</span>
                </a>
                @endforeach
            </div>
        </section>
        @endif

        @if($showSidebarAndFilter ?? false)
        {{-- Sắp xếp theo --}}
        <div class="products-sort-bar">
            <span class="sort-label">Sắp xếp theo:</span>
            @php $priceQ = http_build_query($priceParams); @endphp
            <a href="{{ $sortBaseUrl }}{{ $sortSep }}{{ $priceQ ? $priceQ . '&' : '' }}sort=popular" class="products-sort-btn {{ $currentSort === 'popular' ? 'active' : '' }}">Phổ biến</a>
            <a href="{{ $sortBaseUrl }}{{ $sortSep }}{{ $priceQ ? $priceQ . '&' : '' }}sort=newest" class="products-sort-btn {{ $currentSort === 'newest' ? 'active' : '' }}">Mới nhất</a>
            <a href="{{ $sortBaseUrl }}{{ $sortSep }}{{ $priceQ ? $priceQ . '&' : '' }}sort=bestselling" class="products-sort-btn {{ $currentSort === 'bestselling' ? 'active' : '' }}">Bán chạy</a>
            <div class="products-sort-price">
                <select onchange="location.href=this.value">
                    <option value="{{ $sortBaseUrl }}{{ $sortSep }}{{ $priceQ ? $priceQ . '&' : '' }}sort=popular" {{ $currentSort === 'popular' ? 'selected' : '' }}>Giá</option>
                    <option value="{{ $sortBaseUrl }}{{ $sortSep }}{{ $priceQ ? $priceQ . '&' : '' }}sort=price_asc" {{ $currentSort === 'price_asc' ? 'selected' : '' }}>Giá: Thấp đến cao</option>
                    <option value="{{ $sortBaseUrl }}{{ $sortSep }}{{ $priceQ ? $priceQ . '&' : '' }}sort=price_desc" {{ $currentSort === 'price_desc' ? 'selected' : '' }}>Giá: Cao đến thấp</option>
                </select>
            </div>
        </div>
        @endif

        {{-- Lưới sản phẩm: 6/hàng khi không sidebar, 4/hàng khi có sidebar --}}
        <div class="row">
            @forelse ($products as $product)
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
                    $start = max(1, $current - 2);
                    $end = min($last, $start + 5);
                    if ($end - $start < 5) {
                        $start = max(1, $end - 5);
                    }
                    $elements = [];
                    if ($start > 1) {
                        $elements = [1, '...'];
                    }
                    for ($i = $start; $i <= $end; $i++) {
                        $elements[] = $i;
                    }
                    if ($end < $last) {
                        $elements[] = '...';
                        $elements[] = $last;
                    }
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
    </div>
</div>

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

@if($showSidebarAndFilter ?? false)
<style>
.sidebar-categories-wrap .sidebar-cat-link:nth-child(n+4) { display: none; }
.sidebar-categories-wrap.expanded .sidebar-cat-link:nth-child(n+4) { display: block; }
.sidebar-brands-wrap .sidebar-brand-link:nth-child(n+5) { display: none; }
.sidebar-brands-wrap.expanded .sidebar-brand-link:nth-child(n+5) { display: flex; }
.sidebar-toggle-more { font-size: 0.875rem; color: #dc3545; }
.sidebar-toggle-more:hover { color: #c82333; text-decoration: none; }
</style>
<script>
(function() {
    document.querySelectorAll('.sidebar-toggle-more').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var wrapId = this.getAttribute('data-toggle-wrap');
            var wrap = document.getElementById(wrapId);
            var moreText = this.getAttribute('data-more-text') || 'Xem thêm';
            var lessText = this.getAttribute('data-less-text') || 'Thu gọn';
            if (!wrap) return;
            var label = this.querySelector('.toggle-label');
            if (wrap.classList.toggle('expanded')) {
                if (label) label.textContent = lessText;
            } else {
                if (label) label.textContent = moreText;
            }
        });
    });
})();
</script>
@endif
@endsection
