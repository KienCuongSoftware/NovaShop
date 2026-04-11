@extends('layouts.user')

@section('title', 'NovaShop')

@section('containerClass')
container{{ ($showSidebarAndFilter ?? false) ? ' products-container-wide' : ' products-container-narrow' }}
@endsection

@section('content')
@php
    // Map: product_id => FlashSaleItem rẻ nhất (còn hàng) để hiển thị giá FLASH SALE
    $flashBestItemByProductId = [];
    if (!empty($activeFlashSale ?? null) && !empty($activeFlashSale->items)) {
        foreach ($activeFlashSale->items as $fi) {
            if (($fi->remaining ?? 0) <= 0) continue;
            $pid = $fi->productVariant->product_id ?? null;
            if (!$pid) continue;
            if (!isset($flashBestItemByProductId[$pid]) || (float)$fi->sale_price < (float)$flashBestItemByProductId[$pid]->sale_price) {
                $flashBestItemByProductId[$pid] = $fi;
            }
        }
    }

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

{{-- Removed: "Gợi ý theo danh mục" --}}

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

@if(($activeFlashSale ?? null) && $activeFlashSale->items->isNotEmpty())
<section class="flash-sale-section mb-4" id="welcome-flash-section">
    {{-- Tab khung giờ [00:00] [02:00] [10:00] [12:00] --}}
    @if(isset($todaySlots) && $todaySlots->isNotEmpty())
    <div class="flash-sale-slots-tabs mb-2 d-flex flex-wrap align-items-center" id="welcome-flash-slots">
        @foreach($todaySlots as $slot)
        <span class="flash-sale-slot-tab {{ $slot->id === $activeFlashSale->id ? 'active' : '' }}" data-slot-id="{{ $slot->id }}" data-start="{{ $slot->start_time->format('H:i') }}" title="Khung giờ: {{ $slot->start_time->format('H:i') }} – {{ $slot->end_time->format('H:i') }}">{{ $slot->start_time->format('H:i') }}</span>
        @endforeach
    </div>
    @endif
    <div class="flash-sale-banner d-flex align-items-center justify-content-between flex-wrap mb-3">
        <div class="d-flex align-items-center flash-sale-title-inline">
            <span class="flash-sale-banner-title text-white font-weight-bold">F</span>
            @include('partials.icon-flash-bolt')
            <span class="flash-sale-banner-title text-white font-weight-bold">ASH SALE</span>
        </div>
        <div class="d-flex align-items-center flex-wrap">
            <svg class="flash-sale-clock-icon mr-1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span class="flash-sale-ends-label text-white mr-2">KẾT THÚC TRONG</span>
            <span class="flash-sale-countdown-boxes d-inline-flex align-items-center">
                <span class="flash-sale-box" id="welcome-flash-h">00</span>
                <span class="flash-sale-sep">:</span>
                <span class="flash-sale-box" id="welcome-flash-m">00</span>
                <span class="flash-sale-sep">:</span>
                <span class="flash-sale-box" id="welcome-flash-s">00</span>
            </span>
        </div>
    </div>
    <style>
    .flash-sale-banner { background: linear-gradient(90deg, #c62828 0%, #b71c1c 100%); border-radius: 4px; padding: 0.5rem 1rem; }
    .flash-sale-title-inline .flash-sale-lightning-icon { flex-shrink: 0; margin: 0 -0.05rem 0 0.15rem; vertical-align: middle; }
    .flash-sale-title-inline .flash-sale-banner-title:last-child { margin-left: -0.45rem; }
    .flash-sale-banner-title { font-size: 1rem; letter-spacing: 0.02em; }
    .flash-sale-clock-icon { flex-shrink: 0; color: #fff; }
    .flash-sale-ends-label { font-size: 0.75rem; font-weight: 600; letter-spacing: 0.02em; display: inline-flex; align-items: center; height: 1.75rem; line-height: 1; }
    .flash-sale-countdown-boxes { font-weight: 700; font-size: 1rem; }
    .flash-sale-box { background: #1a1a1a; color: #fff; min-width: 2.25rem; height: 1.75rem; padding: 0 0.45rem; text-align: center; border-radius: 3px; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.15rem; }
    .flash-sale-sep { color: #fff; margin: 0 0.1rem; font-weight: 700; }
    .flash-sale-slots-tabs { gap: 0.25rem; }
    .flash-sale-slot-tab { display: inline-block; padding: 0.35rem 0.6rem; border-radius: 4px; background: #e0e0e0; color: #333; font-weight: 600; font-size: 0.875rem; }
    .flash-sale-slot-tab.active { background: #c62828; color: #fff; }
    .flash-sale-section .flash-sale-card {
        text-decoration: none !important;
        border-radius: 8px;
        padding: 6px;
        transition: background 0.2s ease;
    }
    .flash-sale-section .flash-sale-card:hover { background: rgba(0,0,0,0.06); }
    .flash-sale-track-wrap { position: relative; }
    .flash-sale-track-wrap .flash-sale-track {
        scrollbar-width: none;
        -ms-overflow-style: none;
        padding-left: 44px;
        padding-right: 44px;
    }
    .flash-sale-track-wrap .flash-sale-track::-webkit-scrollbar { display: none; }
    .flash-sale-track-arrow {
        position: absolute; top: 50%; transform: translateY(-50%);
        width: 44px; height: 44px; border-radius: 50%;
        background: #fff; border: 1px solid #dee2e6; box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        display: flex; align-items: center; justify-content: center;
        z-index: 5; cursor: pointer; color: #333; font-size: 1.55rem; line-height: 1;
        transition: background 0.2s, color 0.2s;
    }
    .flash-sale-track-arrow:hover { background: #c62828; color: #fff; border-color: #c62828; }
    .flash-sale-track-arrow:focus,
    .flash-sale-track-arrow:focus-visible { outline: none !important; border-color: #c62828 !important; box-shadow: 0 0 0 2px rgba(198,40,40,0.35); }
    .flash-sale-track-arrow.prev { left: 0; }
    .flash-sale-track-arrow.next { right: 0; }
    .flash-sale-section { box-shadow: 0 6px 18px rgba(0,0,0,0.08); border-radius: 10px; padding-bottom: 0.5rem; }
    </style>
    @php
        $flashDisplayItems = $activeFlashSale->items->filter(fn ($i) => $i->remaining > 0 && ($i->productVariant->product_id ?? null))
            ->unique(fn ($i) => $i->productVariant->product_id)
            ->take(24)
            ->values();
        @endphp
    <div class="flash-sale-track-wrap position-relative">
        <button type="button" class="flash-sale-track-arrow prev" id="welcome-flash-prev" aria-label="Trước">‹</button>
        <button type="button" class="flash-sale-track-arrow next" id="welcome-flash-next" aria-label="Sau">›</button>
        <div class="flash-sale-track overflow-auto pb-2" id="welcome-flash-track" style="display: flex; gap: 1rem; flex-wrap: nowrap; overflow-x: auto; scroll-behavior: smooth;">
        @foreach($flashDisplayItems as $fsItem)
            @php
                $p = $fsItem->productVariant->product ?? null;
                $variant = $fsItem->productVariant;
            @endphp
            @if($p)
            <a href="{{ route('products.show', $p) }}" class="flash-sale-card text-decoration-none text-dark flex-shrink-0" style="width: 160px;">
                <div class="bg-light rounded overflow-hidden mb-2" style="height: 160px;">
                    @if($p->image)
                        <img src="/images/products/{{ basename($p->image) }}" alt="{{ $p->name }}" class="w-100 h-100" style="object-fit: cover;">
                    @else
                        <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted small">N/A</div>
                    @endif
                </div>
                <div class="small font-weight-bold text-truncate" style="max-width: 160px;" title="{{ $p->name }}">{{ Str::limit($p->name, 25) }}</div>
                @php
                    $orig = $variant ? (float) $variant->price : null;
                    $sale = (float) $fsItem->sale_price;
                    $pct = ($orig && $orig > 0 && $orig > $sale) ? (int) round((1 - ($sale / $orig)) * 100) : 0;
                    $pct = max(0, min(99, $pct));
                @endphp
                <div class="text-danger font-weight-bold d-flex align-items-center">
                    <span>{{ number_format($sale, 0, ',', '.') }}₫</span>
                    @if($pct > 0)
                        <span class="badge badge-light text-danger ml-2" style="font-weight: 800;">-{{ $pct }}%</span>
                    @endif
                </div>
                @if($variant && (float) $variant->price > (float) $fsItem->sale_price)
                    <small class="text-muted"><s>{{ number_format($variant->price, 0, ',', '.') }}₫</s></small>
                @endif
            </a>
            @endif
        @endforeach
        </div>
    </div>
</section>
@if($activeFlashSale->end_time)
<script>
(function() {
    var endTime = new Date(@json($activeFlashSale->end_time->toIso8601String())).getTime();
    var timerId = null;
    var apiUrl = @json(route('api.flash-sale'));

    function setCountdownNum(boxEl, val) {
        if (boxEl) boxEl.textContent = val < 10 ? '0' + val : '' + val;
    }

    function renderProducts(items) {
        var track = document.getElementById('welcome-flash-track');
        if (!track || !items || !items.length) return;
        var take = 24;
        var html = '';
        var count = 0;
        var seenProductIds = {};
        for (var i = 0; i < items.length && count < take; i++) {
            var it = items[i];
            if (!it.product || (it.remaining !== undefined && it.remaining <= 0)) continue;
            var pid = it.product.id;
            if (seenProductIds[pid]) continue;
            seenProductIds[pid] = true;
            count++;
            var url = it.product.url || ('/products/' + (it.product.slug || ''));
            var name = (it.product.name || '').substring(0, 25);
            if ((it.product.name || '').length > 25) name += '...';
            var img = it.product.image ? ('/images/products/' + it.product.image.replace(/^.*[/]/, '')) : '';
            var salePrice = typeof it.sale_price === 'number' ? it.sale_price : parseInt(it.sale_price, 10);
            var pct = it.discount_percent || 0;
            var orig = it.variant && it.variant.price ? it.variant.price : salePrice;
            html += '<a href="' + url + '" class="flash-sale-card text-decoration-none text-dark flex-shrink-0" style="width:160px;">';
            html += '<div class="bg-light rounded overflow-hidden mb-2" style="height:160px;">';
            if (img) html += '<img src="' + img + '" alt="" class="w-100 h-100" style="object-fit:cover;">';
            else html += '<div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted small">N/A</div>';
            html += '</div>';
            html += '<div class="small font-weight-bold text-truncate" style="max-width:160px;" title="' + (it.product.name || '') + '">' + name + '</div>';
            html += '<div class="text-danger font-weight-bold d-flex align-items-center">';
            html += '<span>' + (salePrice + '').replace(/\B(?=(\d{3})+(?!\d))/g, '.') + '₫</span>';
            if (pct > 0) html += '<span class="badge badge-light text-danger ml-2" style="font-weight:800;">-' + pct + '%</span>';
            html += '</div>';
            if (orig > salePrice) html += '<small class="text-muted"><s>' + (orig + '').replace(/\B(?=(\d{3})+(?!\d))/g, '.') + '₫</s></small>';
            html += '</a>';
        }
        track.innerHTML = html;
    }

    function formatTime(d) {
        if (!d) return '';
        var h = d.getHours();
        var m = d.getMinutes();
        return (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m;
    }
    function renderSlotTabs(slots, currentId) {
        var wrap = document.getElementById('welcome-flash-slots');
        if (!wrap || !slots || !slots.length) return;
        var html = '';
        for (var i = 0; i < slots.length; i++) {
            var s = slots[i];
            var start = s.start_time ? new Date(s.start_time) : null;
            var end = s.end_time ? new Date(s.end_time) : null;
            var label = formatTime(start) || (s.name || '');
            var title = 'Khung giờ: ' + formatTime(start) + ' – ' + formatTime(end);
            var active = (s.id === currentId) ? ' active' : '';
            html += '<span class="flash-sale-slot-tab' + active + '" data-slot-id="' + s.id + '" title="' + title + '">' + label + '</span>';
        }
        wrap.innerHTML = html;
    }

    function updateSlotTabs(slots, currentId) {
        var wrap = document.getElementById('welcome-flash-slots');
        if (!wrap) return;
        if (slots && slots.length) {
            renderSlotTabs(slots, currentId);
            return;
        }
        var tabs = wrap.querySelectorAll('.flash-sale-slot-tab');
        tabs.forEach(function(tab) {
            var id = parseInt(tab.getAttribute('data-slot-id'), 10);
            if (id === currentId) tab.classList.add('active');
            else tab.classList.remove('active');
        });
    }

    function reloadFlashSale() {
        fetch(apiUrl).then(function(r) { return r.json(); }).then(function(data) {
            if (data.current) {
                endTime = new Date(data.current.end_time).getTime();
                if (data.current.items) renderProducts(data.current.items);
                if (data.slots && data.slots.length) updateSlotTabs(data.slots, data.current.id);
            } else {
                setCountdownNum(document.getElementById('welcome-flash-h'), 0);
                setCountdownNum(document.getElementById('welcome-flash-m'), 0);
                setCountdownNum(document.getElementById('welcome-flash-s'), 0);
            }
            if (timerId) clearInterval(timerId);
            timerId = setInterval(run, 1000);
        }).catch(function() {
            if (timerId) clearInterval(timerId);
            timerId = setInterval(run, 1000);
        });
    }

    var hEl = document.getElementById('welcome-flash-h');
    var mEl = document.getElementById('welcome-flash-m');
    var sEl = document.getElementById('welcome-flash-s');
    if (!hEl || !mEl || !sEl) return;

    function run() {
        var now = new Date().getTime();
        var d = endTime - now;
        if (d <= 0) {
            if (timerId) { clearInterval(timerId); timerId = null; }
            reloadFlashSale();
            return;
        }
        var h = Math.floor((d / (1000 * 60 * 60)) % 24);
        var m = Math.floor((d / (1000 * 60)) % 60);
        var s = Math.floor((d / 1000) % 60);
        setCountdownNum(hEl, h);
        setCountdownNum(mEl, m);
        setCountdownNum(sEl, s);
    }
    run();
    timerId = setInterval(run, 1000);

    (function setupFlashTrackArrows() {
        var track = document.getElementById('welcome-flash-track');
        var prevBtn = document.getElementById('welcome-flash-prev');
        var nextBtn = document.getElementById('welcome-flash-next');
        if (!track || !prevBtn || !nextBtn) return;
        var step = 176;
        function smoothScrollTrack(direction) {
            var start = track.scrollLeft;
            var dist = direction === 'prev' ? -Math.min(step, start) : Math.min(step, track.scrollWidth - track.clientWidth - start);
            if (dist === 0) return;
            var startTime = null;
            function stepAnim(ts) {
                if (!startTime) startTime = ts;
                var elapsed = ts - startTime;
                var duration = 280;
                var t = Math.min(elapsed / duration, 1);
                t = 1 - Math.pow(1 - t, 2);
                track.scrollLeft = start + dist * t;
                if (elapsed < duration) requestAnimationFrame(stepAnim);
            }
            requestAnimationFrame(stepAnim);
        }
        prevBtn.addEventListener('click', function() { smoothScrollTrack('prev'); });
        nextBtn.addEventListener('click', function() { smoothScrollTrack('next'); });
    })();
})();
</script>
@endif
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
                    <span class="brand-check">{!! !($brandSlug ?? null) ? '&#10003;' : '' !!}</span>
                    <span>Tất cả</span>
                </a>
                @foreach($categoryBrands as $b)
                <a href="{{ route('category.products', array_filter(['category' => $category, 'brand' => $b->slug, 'price_min' => $priceMin, 'price_max' => $priceMax])) }}" class="products-sidebar-brand-item sidebar-brand-link {{ ($brandSlug ?? null) === $b->slug ? 'active' : '' }}">
                    <span class="brand-check">{!! ($brandSlug ?? null) === $b->slug ? '&#10003;' : '' !!}</span>
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

        {{-- Rating facet: lọc sản phẩm theo rating >= 1..5 --}}
        @php
            $ratingMinRaw = request()->query('rating');
            $ratingMin = in_array((int) $ratingMinRaw, [1, 2, 3, 4, 5], true) ? (int) $ratingMinRaw : null;
            $ratingBaseParams = array_merge($priceParams, ['sort' => $currentSort]);
        @endphp
        <div class="products-sidebar-rating mt-3">
            <h3 class="products-sidebar-price-title mb-2">Đánh giá</h3>
            <div class="d-flex flex-wrap" style="gap:0.5rem;">
                <a href="{{ $sortBaseUrl }}{{ $sortSep }}{{ http_build_query(array_filter($ratingBaseParams)) }}"
                   class="btn btn-sm {{ $ratingMin === null ? 'btn-outline-danger' : 'btn-light text-muted' }}"
                   style="border-width:2px;">
                    Tất cả
                </a>
                @foreach([5,4,3,2,1] as $r)
                    @php $params = array_merge($ratingBaseParams, ['rating' => $r]); @endphp
                    <a href="{{ $sortBaseUrl }}{{ $sortSep }}{{ http_build_query($params) }}"
                       class="btn btn-sm {{ $ratingMin === $r ? 'btn-outline-danger' : 'btn-light text-muted' }}"
                       style="border-width:2px;">
                        {{ $r }} sao+
                    </a>
                @endforeach
            </div>
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
            <div class="brands-track-wrap position-relative">
                <button type="button" class="brands-track-arrow prev" id="category-brands-prev" aria-label="Trước">‹</button>
                <button type="button" class="brands-track-arrow next" id="category-brands-next" aria-label="Sau">›</button>
                <div class="brands-track" id="category-brands-track">
                    @foreach($categoryBrands as $b)
                    <a href="{{ route('category.products', array_filter(['category' => $category, 'brand' => $b->slug, 'price_min' => $priceMin, 'price_max' => $priceMax])) }}" class="brands-grid-item brands-track-item {{ ($brandSlug ?? null) === $b->slug ? 'active' : '' }}">
                        @if($b->logo)
                        <img src="/images/brands/{{ basename($b->logo) }}" alt="{{ $b->name }}" class="brands-grid-logo" loading="lazy">
                        @endif
                        <span class="brands-grid-name">{{ $b->name }}</span>
                    </a>
                    @endforeach
                </div>
            </div>
        </section>
        <script>
        (function() {
            var track = document.getElementById('category-brands-track');
            var prevBtn = document.getElementById('category-brands-prev');
            var nextBtn = document.getElementById('category-brands-next');
            if (!track || !prevBtn || !nextBtn) return;
            var step = 220;
            function smoothScroll(direction) {
                var start = track.scrollLeft;
                var maxScroll = track.scrollWidth - track.clientWidth;
                var dist = direction === 'prev' ? -Math.min(step, start) : Math.min(step, maxScroll - start);
                if (dist === 0) return;
                var startTime = null;
                function anim(ts) {
                    if (!startTime) startTime = ts;
                    var elapsed = ts - startTime;
                    var duration = 280;
                    var t = Math.min(elapsed / duration, 1);
                    t = 1 - Math.pow(1 - t, 2);
                    track.scrollLeft = start + dist * t;
                    if (elapsed < duration) requestAnimationFrame(anim);
                }
                requestAnimationFrame(anim);
            }
            prevBtn.addEventListener('click', function() { smoothScroll('prev'); });
            nextBtn.addEventListener('click', function() { smoothScroll('next'); });
        })();
        </script>
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
                            @php
                                $ratingAvg = round((float) ($product->approved_reviews_avg_rating ?? 0), 1);
                                $ratingCount = (int) ($product->approved_reviews_count ?? 0);
                            @endphp
                            <div class="small d-flex align-items-center mb-1" style="gap:6px; min-height: 20px;">
                                @if($ratingCount > 0)
                                    <span class="text-warning" aria-hidden="true">
                                        @for($i = 1; $i <= 5; $i++)
                                            {!! $i <= (int) floor($ratingAvg) ? '&#9733;' : '&#9734;' !!}
                                        @endfor
                                    </span>
                                    <span class="text-muted">{{ number_format($ratingAvg, 1, ',', '.') }} ({{ $ratingCount }})</span>
                                @else
                                    <span class="text-muted">Chưa có đánh giá</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-text mb-1 d-flex align-items-center">
                            @php
                                $fi = $flashBestItemByProductId[$product->id] ?? null;
                            @endphp
                            @if($fi)
                                @php
                                    $origVariantPrice = $fi->productVariant->price ?? $product->price;
                                    $salePrice = (float) $fi->sale_price;
                                    $pct = ($origVariantPrice && $origVariantPrice > 0 && $origVariantPrice > $salePrice)
                                        ? (int) round((1 - ($salePrice / $origVariantPrice)) * 100)
                                        : 0;
                                    $pct = max(0, min(99, $pct));
                                @endphp
                                <span class="product-card-price-old">{{ number_format((float)$origVariantPrice, 0, ',', '.') }}₫</span>
                                <span class="product-card-price-new">{{ number_format((float)$fi->sale_price, 0, ',', '.') }}₫</span>
                                @if($pct > 0)
                                    <span class="badge badge-light text-danger ml-auto" style="font-weight: 800;">-{{ $pct }}%</span>
                                @endif
                            @else
                                @php $pct = 0; @endphp
                                @if($product->old_price !== null)
                                    @php
                                        $oldPrice = (float) $product->old_price;
                                        $newPrice = (float) $product->price;
                                        $pct = ($oldPrice > 0 && $oldPrice > $newPrice)
                                            ? (int) round((1 - ($newPrice / $oldPrice)) * 100)
                                            : 0;
                                        $pct = max(0, min(99, $pct));
                                    @endphp
                                    <span class="product-card-price-old">{{ number_format($oldPrice, 0, ',', '.') }}₫</span>
                                @endif

                                <span class="product-card-price-new">{{ number_format($product->price, 0, ',', '.') }}₫</span>
                                @if($pct > 0)
                                    <span class="badge badge-light text-danger ml-auto" style="font-weight: 800;">-{{ $pct }}%</span>
                                @endif
                            @endif
                        </div>
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
<section class="suggested-today mt-5 pt-4 border-top" data-rec-variant="{{ $recVariant ?? 'v1' }}">
    <h2 class="text-center mb-4 font-weight-bold" style="font-size: 1.25rem; color: #f28b82;">GỢI Ý HÔM NAY</h2>
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
                        @php
                            $ratingAvg = round((float) ($product->approved_reviews_avg_rating ?? 0), 1);
                            $ratingCount = (int) ($product->approved_reviews_count ?? 0);
                        @endphp
                        <div class="small d-flex align-items-center mb-1" style="gap:6px; min-height: 20px;">
                            @if($ratingCount > 0)
                                <span class="text-warning" aria-hidden="true">
                                    @for($i = 1; $i <= 5; $i++)
                                        {!! $i <= (int) floor($ratingAvg) ? '&#9733;' : '&#9734;' !!}
                                    @endfor
                                </span>
                                <span class="text-muted">{{ number_format($ratingAvg, 1, ',', '.') }} ({{ $ratingCount }})</span>
                            @else
                                <span class="text-muted">Chưa có đánh giá</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-text mb-1 d-flex align-items-center">
                        @php
                            $fi = $flashBestItemByProductId[$product->id] ?? null;
                        @endphp
                        @if($fi)
                            @php
                                $origVariantPrice = $fi->productVariant->price ?? $product->price;
                                $salePrice = (float) $fi->sale_price;
                                $pct = ($origVariantPrice && $origVariantPrice > 0 && $origVariantPrice > $salePrice)
                                    ? (int) round((1 - ($salePrice / $origVariantPrice)) * 100)
                                    : 0;
                                $pct = max(0, min(99, $pct));
                            @endphp
                            <span class="product-card-price-old">{{ number_format((float)$origVariantPrice, 0, ',', '.') }}₫</span>
                            <span class="product-card-price-new">{{ number_format((float)$fi->sale_price, 0, ',', '.') }}₫</span>
                            @if($pct > 0)
                                <span class="badge badge-light text-danger ml-auto" style="font-weight: 800;">-{{ $pct }}%</span>
                            @endif
                        @else
                            @php $pct = 0; @endphp
                            @if($product->old_price !== null)
                                @php
                                    $oldPrice = (float) $product->old_price;
                                    $newPrice = (float) $product->price;
                                    $pct = ($oldPrice > 0 && $oldPrice > $newPrice)
                                        ? (int) round((1 - ($newPrice / $oldPrice)) * 100)
                                        : 0;
                                    $pct = max(0, min(99, $pct));
                                @endphp
                                <span class="product-card-price-old">{{ number_format($oldPrice, 0, ',', '.') }}₫</span>
                            @endif

                            <span class="product-card-price-new">{{ number_format($product->price, 0, ',', '.') }}₫</span>
                            @if($pct > 0)
                                <span class="badge badge-light text-danger ml-auto" style="font-weight: 800;">-{{ $pct }}%</span>
                            @endif
                        @endif
                    </div>
                    <p class="card-text small product-card-category mb-2">Danh mục: {{ optional($product->category)->name }}</p>
                    @auth
                    <a href="{{ route('products.show', ['product' => $product, 'rec_src' => 'suggested', 'rec_variant' => ($recVariant ?? 'v1')]) }}" class="btn btn-primary btn-view-detail mt-auto">Xem chi tiết</a>
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
