@extends('layouts.user')

@section('title', $product->name . ' - NovaShop')

@section('content')
@php
    $attributeOptions = $product->getAttributeOptionsForFrontend();
    $flashItemsByVariantId = $flashItemsByVariantId ?? [];
    $variantsPayload = $product->variants->map(function ($v) use ($flashItemsByVariantId) {
        $flash = $flashItemsByVariantId[$v->id] ?? null;
        return [
            'id' => $v->id,
            'attr' => $v->attribute_map,
            'price' => (float) $v->price,
            'stock' => (int) $v->stock,
            'image' => $v->main_image_url,
            'flash_price' => $flash ? (float) $flash['sale_price'] : null,
            'flash_remaining' => $flash ? (int) $flash['remaining'] : null,
        ];
    })->values()->all();
    $showFlashCountdown = ($activeFlashSale ?? null) && !empty($flashItemsByVariantId);
    $hasVariants = $product->hasVariants();
    $defaultImage = $product->image ? '/images/products/' . basename($product->image) : null;
    $normalizeUrl = function ($url) {
        if (!$url || !is_string($url)) return null;
        $path = str_replace('\\', '/', $url);
        $base = strtolower(basename(preg_replace('/[#?].*$/', '', $path)));
        return $base ? '/images/products/' . $base : null;
    };
    $byNorm = [];
    $order = [];
    $addOnce = function ($url) use ($normalizeUrl, &$byNorm, &$order) {
        $norm = $normalizeUrl($url);
        if (!$norm || isset($byNorm[$norm])) return;
        $byNorm[$norm] = true;
        $order[] = $norm;
    };
    if ($product->image) {
        $addOnce('/images/products/' . basename($product->image));
    }
    $variants = $product->variants ?? collect();
    if ($variants->isNotEmpty() && !empty($attributeOptions)) {
        $colorAttr = null;
        foreach (array_keys($attributeOptions) as $attrName) {
            if (in_array(strtolower($attrName), ['màu', 'color', 'mau'], true)) {
                $colorAttr = $attrName;
                break;
            }
        }
        $colorAttr = $colorAttr ?? array_key_first($attributeOptions);
        $oneImagePerColor = [];
        foreach ($variants as $v) {
            $colorVal = $v->attribute_map[$colorAttr] ?? null;
            if ($colorVal === null) continue;
            if (!isset($oneImagePerColor[$colorVal]) && $v->main_image_url) {
                $oneImagePerColor[$colorVal] = $v->main_image_url;
            }
        }
        foreach ($oneImagePerColor as $url) {
            $addOnce($url);
        }
    } else {
        foreach ($variants as $v) {
            if ($v->main_image_url) $addOnce($v->main_image_url);
        }
    }
    $galleryImages = $order;
@endphp
<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-transparent p-0">
            <li class="breadcrumb-item"><a href="{{ route('welcome') }}">Trang chủ</a></li>
            @if($product->category)
                @foreach($product->category->getBreadcrumbPath() as $crumb)
                <li class="breadcrumb-item"><a href="{{ route('category.products', $crumb) }}">{{ $crumb->name }}</a></li>
                @endforeach
            @endif
            <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($product->name, 40) }}</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-5 mb-4 mb-md-0">
                    <div class="product-detail-img bg-light rounded overflow-hidden mb-2 position-relative" style="min-height: 280px;" id="main-image-click-area" title="Click để xem ảnh lớn">
                        <img id="main-product-image" src="{{ $defaultImage ?? '' }}" alt="{{ $product->name }}" class="img-fluid w-100" style="object-fit: contain; max-height: 400px; pointer-events: none;" @if(!$defaultImage) style="display:none;" @endif>
                        @if(!$defaultImage && !$hasVariants)
                        <div class="d-flex align-items-center justify-content-center h-100 text-muted py-5">Không có ảnh</div>
                        @endif
                    </div>
                    @if(!empty($galleryImages))
                    {{-- Chỉ hiển thị 4 ảnh, ảnh còn lại ẩn, dùng mũi tên để xem --}}
                    <div class="product-gallery-wrapper position-relative" id="product-gallery-wrapper" style="width: 424px; max-width: 100%; box-sizing: border-box; overflow: hidden;">
                        <button type="button" class="product-gallery-arrow product-gallery-prev border-0 rounded-circle shadow bg-danger text-white" aria-label="Ảnh trước" style="width: 48px; height: 48px; position: absolute; left: 0; top: 50%; transform: translateY(-50%); z-index: 10; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; line-height: 1;">‹</button>
                        <div class="product-gallery-track d-flex flex-nowrap" id="product-gallery" style="scroll-behavior: smooth; overflow-x: auto; width: 344px; min-width: 344px; max-width: 344px; margin: 0 40px; box-sizing: border-box;">
                            @foreach($galleryImages as $gurl)
                            <button type="button" class="product-gallery-thumb product-gallery-thumb-btn rounded flex-shrink-0 product-gallery-thumb-item {{ $loop->first ? 'active' : '' }}" data-src="{{ $gurl }}" style="width: 80px; height: 80px; overflow: hidden; background: #fff; margin-right: 8px; flex-shrink: 0; border: 2px solid #dee2e6; outline: none;">
                                <img src="{{ $gurl }}" alt="" class="w-100 h-100" style="object-fit: cover;">
                            </button>
                            @endforeach
                        </div>
                        <button type="button" class="product-gallery-arrow product-gallery-next border-0 rounded-circle shadow bg-danger text-white" aria-label="Ảnh sau" style="width: 48px; height: 48px; position: absolute; right: 0; top: 50%; transform: translateY(-50%); z-index: 10; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; line-height: 1;">›</button>
                    </div>

                    {{-- Modal xem ảnh lớn --}}
                    <div class="modal fade" id="productImageModal" tabindex="-1" aria-labelledby="productImageModalLabel" aria-hidden="true" data-backdrop="true" data-keyboard="true">
                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable product-image-modal-dialog">
                            <div class="modal-content border-0 shadow-lg">
                                <div class="modal-header border-0 pb-0 py-2 d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1 text-right pr-2">
                                        <h5 class="modal-title text-dark font-weight-bold mb-0" id="productImageModalLabel" style="font-size: 0.95rem;">{{ $product->name }}</h5>
                                    </div>
                                    <button type="button" class="close flex-shrink-0" data-dismiss="modal" aria-label="Đóng">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body pt-2 pb-3 product-image-modal-body">
                                    <div class="d-flex flex-column flex-lg-row align-items-start justify-content-center">
                                        <div class="position-relative flex-grow-1 mb-3 mb-lg-0 text-center product-lightbox-main-wrap">
                                            <button type="button" class="product-lightbox-prev btn btn-light border rounded-circle shadow-sm position-absolute" style="left: 8px; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; z-index: 5;" aria-label="Ảnh trước">‹</button>
                                            <img id="product-lightbox-image" src="" alt="{{ $product->name }}" class="img-fluid product-lightbox-img">
                                            <button type="button" class="product-lightbox-next btn btn-light border rounded-circle shadow-sm position-absolute" style="right: 8px; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; z-index: 5;" aria-label="Ảnh sau">›</button>
                                        </div>
                                        <div class="product-lightbox-thumbs ml-lg-3 pl-lg-2 mt-3 mt-lg-0 product-lightbox-thumbs-wrap align-self-start">
                                            @foreach($galleryImages as $gurl)
                                            <button type="button" class="product-lightbox-thumb border rounded p-0 overflow-hidden {{ $loop->first ? 'active' : '' }}" data-src="{{ $gurl }}">
                                                <img src="{{ $gurl }}" alt="" class="w-100 h-100" style="object-fit: cover;">
                                            </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <style>
                    /* Modal ảnh sản phẩm */
                    #productImageModal .product-image-modal-dialog {
                        max-width: min(720px, 90%);
                        margin-left: auto;
                        margin-right: auto;
                    }
                    #productImageModal .product-image-modal-body {
                        max-height: 75vh;
                        overflow-y: auto;
                    }
                    #productImageModal .product-lightbox-main-wrap {
                        min-height: 200px;
                        max-height: 58vh;
                    }
                    #productImageModal .product-lightbox-img {
                        max-height: 58vh;
                        object-fit: contain;
                    }
                    /* Thumbnail bên phải: 3 ảnh mỗi dòng, ảnh to hơn, nằm cao */
                    #productImageModal .product-lightbox-thumbs-wrap {
                        display: grid;
                        grid-template-columns: repeat(3, 1fr);
                        gap: 8px;
                        max-width: 280px;
                        max-height: 58vh;
                        overflow-y: auto;
                        scrollbar-width: thin;
                    }
                    #productImageModal .product-lightbox-thumb {
                        width: 100%;
                        aspect-ratio: 1;
                        background: #fff;
                        border: 2px solid #dee2e6 !important;
                        outline: none;
                        -webkit-appearance: none;
                        appearance: none;
                    }
                    #productImageModal .product-lightbox-thumb:focus,
                    #productImageModal .product-lightbox-thumb:focus-visible {
                        outline: none !important;
                        border: 2px solid #dc3545 !important;
                        box-shadow: 0 0 0 2px rgba(220,53,69,0.35);
                    }
                    #productImageModal .product-lightbox-thumb.active {
                        border: 2px solid #dc3545 !important;
                        box-shadow: 0 0 0 2px rgba(220,53,69,0.35);
                    }
                    #productImageModal .product-lightbox-thumbs-wrap::-webkit-scrollbar { width: 6px; }
                    #productImageModal .product-lightbox-thumbs-wrap::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }
                    #main-image-click-area[data-has-img] { cursor: pointer; }
                    </style>
                    <script>
                    (function() {
                        var galleryUrls = @json($galleryImages);
                        if (!galleryUrls || galleryUrls.length === 0) return;
                        var mainImg = document.getElementById('main-product-image');
                        var clickArea = document.getElementById('main-image-click-area');
                        if (clickArea && galleryUrls.length > 0) clickArea.setAttribute('data-has-img', '1');
                        var modal = document.getElementById('productImageModal');
                        var lightboxImg = document.getElementById('product-lightbox-image');
                        var thumbs = document.querySelectorAll('.product-lightbox-thumb');
                        var prevBtn = document.querySelector('.product-lightbox-prev');
                        var nextBtn = document.querySelector('.product-lightbox-next');
                        function getCurrentIndex() {
                            var src = lightboxImg && lightboxImg.src ? lightboxImg.src.replace(/^https?:\/\/[^/]+/, '') : '';
                            for (var i = 0; i < galleryUrls.length; i++) {
                                if (src.indexOf(galleryUrls[i]) !== -1 || galleryUrls[i].indexOf(src) !== -1) return i;
                                if (src && (src === galleryUrls[i] || src.endsWith(galleryUrls[i].replace(/^\//, '')))) return i;
                            }
                            return 0;
                        }
                        function setLightboxIndex(i) {
                            i = (i + galleryUrls.length) % galleryUrls.length;
                            if (lightboxImg) lightboxImg.src = galleryUrls[i];
                            thumbs.forEach(function(t, j) { t.classList.toggle('active', j === i); });
                            return i;
                        }
                        if (clickArea && modal && lightboxImg) {
                            clickArea.addEventListener('click', function() {
                                var src = mainImg && mainImg.src ? mainImg.src : galleryUrls[0];
                                if (!src || src === '') return;
                                lightboxImg.src = src;
                                var idx = 0;
                                galleryUrls.forEach(function(u, i) { if (src.indexOf(u) !== -1 || (u && src.endsWith(u.replace(/^\//, '')))) idx = i; });
                                setLightboxIndex(idx);
                                if (typeof $ !== 'undefined' && $.fn.modal) $('#productImageModal').modal('show');
                            });
                        }
                        if (prevBtn) prevBtn.addEventListener('click', function() { setLightboxIndex(getCurrentIndex() - 1); });
                        if (nextBtn) nextBtn.addEventListener('click', function() { setLightboxIndex(getCurrentIndex() + 1); });
                        thumbs.forEach(function(thumb, i) {
                            thumb.addEventListener('click', function() { setLightboxIndex(i); });
                        });
                    })();
                    </script>
                    <style>
                    #product-gallery .product-gallery-thumb-btn,
                    #product-gallery button.product-gallery-thumb {
                        border: 2px solid #dee2e6 !important;
                        outline: none !important;
                        -webkit-appearance: none;
                        appearance: none;
                    }
                    #product-gallery .product-gallery-thumb-btn:focus,
                    #product-gallery .product-gallery-thumb-btn:focus-visible,
                    #product-gallery button.product-gallery-thumb:focus,
                    #product-gallery button.product-gallery-thumb:focus-visible {
                        outline: none !important;
                        border: 2px solid #dc3545 !important;
                        border-color: #dc3545 !important;
                        box-shadow: 0 0 0 2px rgba(220,53,69,0.5);
                    }
                    #product-gallery .product-gallery-thumb-btn.active,
                    #product-gallery button.product-gallery-thumb.active {
                        border: 2px solid #dc3545 !important;
                        border-color: #dc3545 !important;
                        box-shadow: 0 0 0 2px rgba(220,53,69,0.5);
                    }
                    </style>
                    @endif
                </div>
                <div class="col-md-7">
                    <h1 class="h4 font-weight-bold text-dark mb-3">{{ $product->name }}</h1>

                    @if($product->category)
                    <p class="text-muted small mb-2">Danh mục: <a href="{{ route('category.products', $product->category) }}" class="text-danger">{{ $product->category->full_path }}</a></p>
                    @endif
                    @if($product->brand)
                    <p class="text-muted small mb-2">Thương hiệu: {{ $product->brand->name }}</p>
                    @endif

                    <div class="mb-3" id="product-price-wrap">
                        @if($product->old_price !== null)
                            <span class="text-muted mr-2" id="product-old-price" style="text-decoration: line-through; font-size: 1rem;">{{ number_format($product->old_price, 0, ',', '.') }}₫</span>
                        @endif
                        <span class="text-danger font-weight-bold" id="product-price" style="font-size: 1.5rem;">{{ number_format($hasVariants ? ($product->variants->first()->price ?? $product->price) : $product->price, 0, ',', '.') }}₫</span>
                    </div>

                    @include('user.products._shipping_estimate')

                    @if($hasVariants)
                    <div class="mb-3" id="variant-options-wrap">
                        @if(!empty($showFlashCountdown) && !empty($flashSaleEndTime ?? null))
                        <div class="flash-sale-card mb-3" id="flash-sale-card" style="{{ $showFlashCountdown ? '' : 'display:none;' }}">
                            <div class="flash-sale-banner d-flex align-items-center justify-content-between flex-wrap" id="flash-sale-countdown-wrap">
                                <div class="d-flex align-items-center flash-sale-title-inline">
                                    <span class="flash-sale-banner-title text-white font-weight-bold">F</span>
                                    @include('partials.icon-flash-bolt')
                                    <span class="flash-sale-banner-title text-white font-weight-bold">ASH SALE</span>
                                </div>
                                <div class="d-flex align-items-center flex-wrap">
                                    <svg class="flash-sale-clock-icon mr-1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    <span class="flash-sale-ends-label text-white mr-2">KẾT THÚC TRONG</span>
                                    <span class="flash-sale-countdown-boxes d-inline-flex align-items-center" id="flash-sale-countdown">
                                        <span class="flash-sale-box" id="flash-sale-h">00</span>
                                        <span class="flash-sale-sep">:</span>
                                        <span class="flash-sale-box" id="flash-sale-m">00</span>
                                        <span class="flash-sale-sep">:</span>
                                        <span class="flash-sale-box" id="flash-sale-s">00</span>
                                    </span>
                                </div>
                            </div>
                            <div class="flash-sale-price-row d-flex align-items-center flex-wrap" id="flash-sale-price-row">
                            <span class="flash-sale-price" id="flash-sale-price">0₫</span>
                            <span class="flash-sale-old-price text-white-50 ml-2" id="flash-sale-old-price" style="display:none;"><s>0₫</s></span>
                            <span class="flash-sale-discount badge badge-light text-danger ml-2" id="flash-sale-discount" style="display:none;">-0%</span>
                        </div>
                        </div>
                        <style>
                        .flash-sale-card { border-radius: 4px; overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.08); }
                        .flash-sale-banner { background: linear-gradient(90deg, #c62828 0%, #b71c1c 100%); padding: 0.5rem 1rem; }
                        .flash-sale-title-inline .flash-sale-lightning-icon { flex-shrink: 0; margin: 0 -0.05rem 0 0.15rem; vertical-align: middle; }
                        .flash-sale-title-inline .flash-sale-banner-title:last-child { margin-left: -0.45rem; }
                        .flash-sale-banner-title { font-size: 1rem; letter-spacing: 0.02em; }
                        .flash-sale-clock-icon { flex-shrink: 0; color: #fff; }
                        .flash-sale-ends-label { font-size: 0.75rem; font-weight: 600; letter-spacing: 0.02em; display: inline-flex; align-items: center; height: 1.75rem; line-height: 1; }
                        .flash-sale-countdown-boxes { font-weight: 700; font-size: 1rem; }
                        .flash-sale-box { background: #1a1a1a; color: #fff; min-width: 2.25rem; height: 1.75rem; padding: 0 0.45rem; text-align: center; border-radius: 3px; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.15rem; }
                        .flash-sale-sep { color: #fff; margin: 0 0.1rem; font-weight: 700; }
                        .flash-sale-price-row { background: #fff0f1; padding: 0.55rem 1rem; }
                        /* Khi KHÔNG có flash sale thì vẫn giữ nền/padding giống block flash để UI đồng nhất */
                        #product-price-wrap { background: #fff0f1; padding: 0.55rem 1rem; border-radius: 4px; }
                        .flash-sale-price { color: #dc3545; font-weight: 900; font-size: 1.35rem; letter-spacing: 0.01em; }
                        .flash-sale-old-price { color: rgba(0,0,0,0.5) !important; }
                        .flash-sale-discount { font-weight: 800; }
                        </style>
                        @endif
                        @foreach($attributeOptions as $attrName => $values)
                        <p class="mb-2 font-weight-bold">{{ $attrName }}</p>
                        <div class="d-flex flex-wrap mb-3 variant-buttons" data-attribute="{{ $attrName }}">
                            @foreach($values as $val)
                            <button type="button" class="btn variant-option" data-attribute="{{ $attrName }}" data-value="{{ $val }}">{{ $val }}</button>
                            @endforeach
                        </div>
                        @endforeach
                        <p class="text-muted small mb-2" id="variant-stock" aria-hidden="true"></p>
                    </div>
                    @elseif($product->quantity !== null)
                    @endif

                    <div class="mt-4">
                        @auth
                        <div class="d-flex flex-wrap align-items-center mb-3" style="gap: 1rem;">
                            <div class="d-flex align-items-center">
                                <span class="text-muted small mr-2">Số lượng</span>
                                <div class="input-group input-group-sm product-quantity-wrap" style="width: 130px;">
                                    <div class="input-group-prepend">
                                        <button type="button" class="btn btn-outline-secondary product-qty-minus" id="product-qty-minus" aria-label="Giảm" @if($hasVariants) disabled @endif>−</button>
                                    </div>
                                    <input type="number" id="product-quantity-input" class="form-control form-control-sm text-center product-quantity-input" value="1" min="1" max="{{ $hasVariants ? ($product->variants->max('stock') ?: 999) : ($product->quantity ?? 999) }}" step="1" style="font-weight: 600; color: #dc3545;" @if($hasVariants) readonly tabindex="-1" @endif>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary product-qty-plus" id="product-qty-plus" aria-label="Tăng" @if($hasVariants) disabled @endif>+</button>
                                    </div>
                                </div>
                                @if($hasVariants) @php $totalStock = $product->variants->sum('stock'); @endphp @endif
                                <span class="small ml-2 {{ $hasVariants ? 'product-stock-status' : 'text-muted' }}" id="product-stock-label">
                                    @if($hasVariants)
                                    {{ $totalStock > 0 ? 'CÒN HÀNG' : 'HẾT HÀNG' }}
                                    @elseif($product->quantity !== null)
                                    {{ number_format($product->quantity, 0, ',', '.') }} sản phẩm có sẵn
                                    @endif
                                </span>
                            </div>
                        </div>
                        <form action="{{ route('cart.add') }}" method="POST" class="d-inline" id="add-to-cart-form">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            @if($hasVariants)
                            <input type="hidden" name="product_variant_id" value="" id="product_variant_id">
                            @endif
                            <input type="hidden" name="quantity" id="add-to-cart-quantity" value="1">
                            <button type="submit" class="btn btn-danger px-4" id="btn-add-cart" @if($hasVariants) disabled @endif>Thêm vào giỏ</button>
                        </form>
                        <style>
                            #btn-add-cart { border-radius: 10px; }
                            .product-secondary-actions { gap: 0.5rem !important; }
                            .product-secondary-actions .product-action-btn {
                                border-radius: 10px;
                                padding: 0.45rem 1rem;
                                font-size: 0.875rem;
                                font-weight: 600;
                                display: inline-flex;
                                align-items: center;
                                gap: 0.4rem;
                                border-width: 2px;
                                line-height: 1.2;
                                transition: background-color .15s ease, color .15s ease, border-color .15s ease, box-shadow .15s ease;
                            }
                            .product-secondary-actions .product-action-btn svg {
                                width: 17px;
                                height: 17px;
                                flex-shrink: 0;
                            }
                            .product-secondary-actions .product-action-btn:disabled {
                                opacity: 0.55;
                                cursor: not-allowed;
                            }
                            .product-secondary-actions .product-action-btn.btn-outline-compare {
                                color: #495057;
                                border-color: #ced4da;
                                background: #fff;
                            }
                            .product-secondary-actions .product-action-btn.btn-outline-compare:hover:not(:disabled) {
                                color: #dc3545;
                                border-color: #dc3545;
                                background: #fff5f5;
                            }
                            .product-secondary-actions .product-action-btn.btn-outline-stock {
                                color: #c82333;
                                border-color: #f5b5bd;
                                background: #fffafb;
                            }
                            .product-secondary-actions .product-action-btn.btn-outline-stock:hover:not(:disabled) {
                                border-color: #dc3545;
                                background: #fff0f1;
                                box-shadow: 0 2px 8px rgba(220, 53, 69, 0.12);
                            }
                            .product-secondary-actions .badge-stock-pill {
                                border-radius: 10px;
                                padding: 0.45rem 1rem;
                                font-weight: 600;
                            }
                        </style>
                        <div class="mt-3 d-flex flex-wrap align-items-center product-secondary-actions">
                            <form action="{{ route('wishlist.toggle') }}" method="POST" class="d-inline mb-0">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <button type="submit" class="btn btn-sm product-action-btn {{ ($inWishlist ?? false) ? 'btn-danger' : 'btn-outline-danger' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="{{ ($inWishlist ?? false) ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                                    {{ ($inWishlist ?? false) ? 'Đã yêu thích' : 'Yêu thích' }}
                                </button>
                            </form>
                            <form action="{{ route('compare.add') }}" method="POST" class="d-inline mb-0">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <button type="submit" class="btn btn-sm product-action-btn btn-outline-compare" @if($onCompare ?? false) disabled @endif>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="7" height="14" rx="1"/><rect x="14" y="5" width="7" height="14" rx="1"/><path d="M10 9h4M10 15h4"/></svg>
                                    So sánh
                                </button>
                            </form>
                            @if(!$hasVariants && (int) $product->quantity <= 0)
                                @if($stockSubscribedSimple ?? false)
                                    <span class="badge badge-secondary badge-stock-pill align-self-center">Đã đăng ký thông báo</span>
                                @else
                                    <form action="{{ route('stock-notifications.store') }}" method="POST" class="d-inline mb-0">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                        <button type="submit" class="btn btn-sm product-action-btn btn-outline-stock">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                                            <span class="product-action-btn-label">Báo khi có hàng</span>
                                        </button>
                                    </form>
                                @endif
                            @endif
                            @if($hasVariants)
                                <form id="stock-notify-form" action="{{ route('stock-notifications.store') }}" method="POST" class="d-inline mb-0" style="display: none;">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <input type="hidden" name="product_variant_id" id="stock-notify-variant-id" value="">
                                    <button type="submit" class="btn btn-sm product-action-btn btn-outline-stock" id="stock-notify-btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                                        <span class="product-action-btn-label">Báo khi có hàng</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                        @else
                        <a href="{{ route('login') }}" class="btn btn-danger">Đăng nhập để thêm vào giỏ</a>
                        @endauth
                    </div>
                </div>
            </div>
            @if($product->description)
            <div class="row mt-4">
                <div class="col-12">
                    <div class="border-top pt-4">
                        <h6 class="font-weight-bold text-dark mb-2">Mô tả sản phẩm</h6>
                        <p class="text-secondary mb-0" style="white-space: pre-line;">{{ $product->description }}</p>
                    </div>
                </div>
            </div>
            @endif

            @if(isset($boughtTogetherProducts) && $boughtTogetherProducts->isNotEmpty())
            <div class="row mt-4">
                <div class="col-12 border-top pt-4">
                    <h6 class="font-weight-bold text-dark mb-3">Thường mua cùng</h6>
                    <div class="row">
                        @foreach($boughtTogetherProducts as $bp)
                        <div class="col-6 col-md-3 mb-3">
                            <a href="{{ route('products.show', $bp) }}" class="text-dark d-block card h-100 border shadow-sm">
                                @if($bp->image)
                                    <img src="/images/products/{{ basename($bp->image) }}" class="card-img-top p-2" alt="" style="height: 120px; object-fit: contain;">
                                @endif
                                <div class="card-body p-2">
                                    <div class="small font-weight-bold">{{ Str::limit($bp->name, 42) }}</div>
                                    <div class="text-danger small">{{ number_format($bp->effective_price, 0, ',', '.') }}₫</div>
                                </div>
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Đánh giá sản phẩm --}}
            <div class="row mt-4">
                <div class="col-12">
                    @include('user.products._reviews_block', [
                        'product' => $product,
                        'reviewCount' => $reviewCount,
                        'avgRating' => $avgRating,
                        'reviewDistribution' => $reviewDistribution,
                        'reviews' => $reviews,
                        'myReview' => $myReview ?? null,
                        'canReviewProduct' => $canReviewProduct ?? false,
                    ])
                </div>
            </div>
        </div>
    </div>
</div>

                    <script>
                        (function() {
                            // AJAX partial loading for review filter/pagination (không load lại trang).
                            function appendPartialFlag(url) {
                                try {
                                    var u = new URL(url, window.location.origin);
                                    u.searchParams.set('reviews_partial', '1');
                                    return u.toString();
                                } catch (e) {
                                    return url;
                                }
                            }

                            function stripPartialFlag(url) {
                                try {
                                    var u = new URL(url, window.location.origin);
                                    u.searchParams.delete('reviews_partial');
                                    return u.toString();
                                } catch (e) {
                                    return url;
                                }
                            }

                            document.addEventListener('click', function(e) {
                                var block = document.getElementById('product-reviews-block');
                                if (!block) return;
                                if (!block.contains(e.target)) return;

                                var a = e.target.closest('a');
                                if (!a) return;

                                var href = a.getAttribute('href');
                                if (!href || href === 'javascript:void(0)') return;

                                var isFilterOrPage = a.classList.contains('review-filter-btn') || a.classList.contains('page-link');
                                if (!isFilterOrPage) return;

                                e.preventDefault();
                                e.stopPropagation();

                                var fetchUrl = appendPartialFlag(href);
                                var pushUrl = stripPartialFlag(href);

                                // Cập nhật URL nhưng không reload.
                                try { window.history.pushState({}, '', pushUrl); } catch (err) {}

                                fetch(fetchUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                    .then(function(r) { return r.text(); })
                                    .then(function(html) {
                                        var tmp = document.createElement('div');
                                        tmp.innerHTML = html;
                                        var newBlock = tmp.querySelector('#product-reviews-block');
                                        if (!newBlock) return;
                                        block.replaceWith(newBlock);
                                    })
                                    .catch(function() {
                                        // Nếu lỗi thì để nguyên (không reload lại).
                                    });
                            }, true);
                        })();
                    </script>

@if($hasVariants)
<style>
#variant-options-wrap .variant-buttons .variant-option,
#variant-options-wrap button.variant-option {
    min-width: 52px;
    padding: 0.5rem 1rem;
    font-size: 1rem;
    border-radius: 0.5rem;
    border: 1px solid #ced4da !important;
    border-color: #ced4da !important;
    background: #f1f3f5;
    color: #333;
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
    outline: none !important;
}
#variant-options-wrap .variant-buttons .variant-option:hover:not(:disabled),
#variant-options-wrap button.variant-option:hover:not(:disabled) {
    border-color: #dc3545 !important;
    background: #dc3545;
    color: #fff;
}
#variant-options-wrap .variant-buttons .variant-option:focus,
#variant-options-wrap button.variant-option:focus {
    outline: none !important;
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.4);
}
#variant-options-wrap .variant-buttons .variant-option.active,
#variant-options-wrap button.variant-option.active {
    border: 2px solid #dc3545 !important;
    border-color: #dc3545 !important;
    background: #dc3545 !important;
    color: #fff !important;
    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.35);
}
#variant-options-wrap .variant-buttons .variant-option:disabled,
#variant-options-wrap button.variant-option:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
<script>
window.productHasVariants = true;
(function() {
    var variants = @json($variantsPayload);
    var defaultImage = @json($defaultImage);
    var flashSaleEndTime = @json($flashSaleEndTime ?? null);
    var attrNames = @json($hasVariants && !empty($attributeOptions) ? array_keys($attributeOptions) : []);
    var firstAttrName = @json($hasVariants && !empty($attributeOptions) ? array_key_first($attributeOptions) : null);
    var colorAttrName = (function() {
        for (var i = 0; i < attrNames.length; i++) {
            var n = (attrNames[i] || '').toString().toLowerCase();
            if (n === 'màu' || n === 'mau' || n === 'color' || n.indexOf('màu') !== -1 || n.indexOf('color') !== -1) return attrNames[i];
        }
        return firstAttrName;
    })();
    var sizeAttrName = (function() {
        for (var i = 0; i < attrNames.length; i++) {
            var n = (attrNames[i] || '').toString().toLowerCase();
            if (n === 'size' || n.indexOf('size') !== -1 || n.indexOf('kích') !== -1 || n.indexOf('kich') !== -1) return attrNames[i];
        }
        return null;
    })();
    var mainImg = document.getElementById('main-product-image');
    var priceEl = document.getElementById('product-price');
    var priceWrapEl = document.getElementById('product-price-wrap');
    var flashCardEl = document.getElementById('flash-sale-card');

    function formatMoneyVn(value) {
        var n = Number(value);
        if (!Number.isFinite(n)) n = 0;
        return n.toLocaleString('vi-VN') + '₫';
    }
    var flashPriceRow = document.getElementById('flash-sale-price-row');
    var flashPriceEl = document.getElementById('flash-sale-price');
    var flashOldPriceEl = document.getElementById('flash-sale-old-price');
    var flashDiscountEl = document.getElementById('flash-sale-discount');
    var variantInput = document.getElementById('product_variant_id');
    var stockEl = document.getElementById('variant-stock');
    var btnAdd = document.getElementById('btn-add-cart');
    var form = document.getElementById('add-to-cart-form');
    var selection = {};
    var stockSubscribedIds = @json(($stockSubscribedVariantIds ?? collect())->values()->all());

    function updateStockNotifyVisibility() {
        var form = document.getElementById('stock-notify-form');
        var btn = document.getElementById('stock-notify-btn');
        var vidInput = document.getElementById('stock-notify-variant-id');
        if (!form || !btn || !vidInput) return;
        var v = findVariant();
        if (v) {
            var effectiveStock = (v.flash_remaining != null && v.flash_remaining > 0) ? Math.min(v.stock, v.flash_remaining) : v.stock;
            vidInput.value = v.id;
            if (effectiveStock < 1) {
                form.style.display = 'inline';
                var sub = stockSubscribedIds.indexOf(v.id) !== -1;
                btn.disabled = sub;
                var stockLbl = btn.querySelector('.product-action-btn-label');
                if (stockLbl) stockLbl.textContent = sub ? 'Đã đăng ký' : 'Báo khi có hàng';
            } else {
                form.style.display = 'none';
            }
        } else {
            form.style.display = 'none';
        }
    }

    function findVariant() {
        for (var i = 0; i < variants.length; i++) {
            var v = variants[i];
            var match = true;
            for (var k in v.attr) {
                if ((selection[k] || '') !== (v.attr[k] || '')) {
                    match = false;
                    break;
                }
            }
            for (var k in selection) {
                if (selection[k] && (v.attr[k] || '') !== selection[k]) {
                    match = false;
                    break;
                }
            }
            if (match) return v;
        }
        return null;
    }

    function findFirstVariantMatchingSelection() {
        for (var i = 0; i < variants.length; i++) {
            var v = variants[i];
            var match = true;
            for (var k in selection) {
                if (selection[k] && (v.attr[k] || '') !== selection[k]) {
                    match = false;
                    break;
                }
            }
            if (match) return v;
        }
        return null;
    }

    function findFirstVariantWithSameColorAndImage(colorVal) {
        if (!colorVal) return null;
        for (var i = 0; i < variants.length; i++) {
            var v = variants[i];
            if ((v.attr[colorAttrName] || '') !== colorVal) continue;
            // Chỉ cần variant cùng màu và có ảnh => ưu tiên ảnh đó cho main image.
            if (v.image) return v;
        }
        return null;
    }

    function getAvailableValues(attrName) {
        var available = {};
        variants.forEach(function(v) {
            var otherMatch = true;
            for (var k in selection) {
                if (k === attrName) continue;
                if (selection[k] && (v.attr[k] || '') !== selection[k]) {
                    otherMatch = false;
                    break;
                }
            }
            if (otherMatch && v.attr[attrName]) {
                var effectiveStock = (v.flash_remaining != null && v.flash_remaining > 0)
                    ? Math.min(v.stock || 0, v.flash_remaining)
                    : (v.stock || 0);
                if (effectiveStock > 0) {
                    available[v.attr[attrName]] = true;
                }
            }
        });
        return available;
    }

    var totalStock = variants.reduce(function(s, v) { return s + (v.stock || 0); }, 0);

    function updateQtyMaxWarning() {
        if (!stockEl) return;
        // Chỉ hiển thị warning khi đã chọn đủ variant (có id trong input ẩn).
        if (!variantInput || !variantInput.value) return;

        var qtyInput = document.getElementById('product-quantity-input');
        if (!qtyInput) return;

        var val = parseInt(qtyInput.value, 10) || 1;
        var max = parseInt(qtyInput.getAttribute('max'), 10) || 999;
        var isMax = val >= max;

        if (isMax) {
            stockEl.textContent = 'Số lượng bạn chọn đã đạt mức tối đa của sản phẩm này';
            stockEl.classList.remove('text-muted');
            stockEl.classList.add('text-danger');
            // Giữ hiển thị trên cùng 1 hàng như dòng "SẢN PHẨM CÓ SẴN" (tránh xuống dòng do flex-wrap).
            stockEl.style.whiteSpace = 'nowrap';
            stockEl.style.fontSize = '0.78rem';
            stockEl.style.lineHeight = '1.1';
            stockEl.style.overflow = 'hidden';
            stockEl.style.textOverflow = 'ellipsis';
            return;
        }

        // Không đạt max -> xóa nội dung & trả về style trung tính.
        stockEl.textContent = '';
        stockEl.classList.add('text-muted');
        stockEl.classList.remove('text-danger');
        stockEl.style.whiteSpace = '';
        stockEl.style.fontSize = '';
        stockEl.style.lineHeight = '';
        stockEl.style.overflow = '';
        stockEl.style.textOverflow = '';
    }

    function updateState() {
        var v = findVariant();
        document.querySelectorAll('.variant-option').forEach(function(btn) {
            var attr = btn.getAttribute('data-attribute');
            var val = btn.getAttribute('data-value');
            btn.classList.toggle('active', selection[attr] === val);
            var available = getAvailableValues(attr);
            btn.disabled = !available[val];
        });
        var qtyInput = document.getElementById('product-quantity-input');
        var stockLabel = document.getElementById('product-stock-label');
        var qtyMinusBtn = document.getElementById('product-qty-minus');
        var qtyPlusBtn = document.getElementById('product-qty-plus');
        if (v) {
            variantInput.value = v.id;
            var effectivePrice = (v.flash_price != null && v.flash_remaining > 0) ? v.flash_price : v.price;
            var effectiveStock = (v.flash_remaining != null && v.flash_remaining > 0) ? Math.min(v.stock, v.flash_remaining) : v.stock;
            priceEl.textContent = formatMoneyVn(effectivePrice);
            if (v.flash_price != null && v.flash_remaining > 0) priceEl.classList.add('text-danger'); else priceEl.classList.remove('text-danger');
            if (flashPriceRow && flashPriceEl) {
                if (v.flash_price != null && v.flash_remaining > 0) {
                    if (flashCardEl) flashCardEl.style.display = '';
                    flashPriceRow.style.display = '';
                    flashPriceEl.textContent = formatMoneyVn(effectivePrice);
                    if (flashOldPriceEl) {
                        flashOldPriceEl.style.display = '';
                        flashOldPriceEl.innerHTML = '<s>' + formatMoneyVn(v.price) + '</s>';
                    }
                    if (flashDiscountEl) {
                        var pct = v.price > 0 ? Math.round((1 - (effectivePrice / v.price)) * 100) : 0;
                        pct = Math.max(0, Math.min(99, pct));
                        flashDiscountEl.style.display = pct > 0 ? '' : 'none';
                        flashDiscountEl.textContent = '-' + pct + '%';
                    }
                    if (priceWrapEl) priceWrapEl.style.display = 'none';
                } else {
                    flashPriceRow.style.display = 'none';
                    if (flashCardEl) flashCardEl.style.display = 'none';
                    if (priceWrapEl) priceWrapEl.style.display = '';
                }
            }
            // Removed: dòng "Còn lại: X sản phẩm" khi đã chọn đủ thuộc tính.
            // Sẽ hiển thị thông báo màu đỏ khi người dùng chọn số lượng chạm max.
            if (stockEl) stockEl.textContent = '';
            btnAdd.disabled = effectiveStock < 1;
            if (qtyInput) {
                qtyInput.max = Math.max(1, effectiveStock);
                qtyInput.readOnly = false;
                qtyInput.removeAttribute('tabindex');
                var val = parseInt(qtyInput.value, 10) || 1;
                qtyInput.value = Math.min(Math.max(1, val), effectiveStock);
                syncQuantityToHidden();
            }
            if (qtyMinusBtn) qtyMinusBtn.disabled = false;
            if (qtyPlusBtn) qtyPlusBtn.disabled = false;
            var displayStock = (v.flash_remaining != null && v.flash_remaining > 0) ? Math.min(v.stock, v.flash_remaining) : v.stock;
            if (stockLabel) {
                stockLabel.textContent = displayStock.toLocaleString('vi-VN') + ' SẢN PHẨM CÓ SẴN';
                stockLabel.classList.add('product-stock-status');
            }
            if (mainImg) {
                // Khi đã chọn đủ thuộc tính (ra được 1 variant cụ thể) => hiển thị đúng ảnh của variant đó.
                mainImg.src = (v && v.image) ? v.image : (defaultImage || (variants[0] && variants[0].image) || '');
                mainImg.style.display = mainImg.src ? '' : 'none';
            }
            setGalleryActive(mainImg ? mainImg.src : defaultImage);
        } else {
            variantInput.value = '';
            priceEl.textContent = formatMoneyVn(@json($product->variants->first()->price ?? $product->price));
            // Nếu chỉ chọn Màu (chưa chọn Size) => hiển thị tổng tồn kho của màu đó (cộng tất cả size)
            var selectedColor = colorAttrName ? (selection[colorAttrName] || '') : '';
            var selectedSize = sizeAttrName ? (selection[sizeAttrName] || '') : '';
            if (selectedColor && (!sizeAttrName || !selectedSize)) {
                // Ẩn dòng "Màu X: còn lại ... (tất cả size)" khi người dùng mới chọn màu.
                stockEl.textContent = '';
            } else {
                stockEl.textContent = '';
            }
            btnAdd.disabled = true;
            if (flashPriceRow && flashPriceEl) {
                // Chưa chọn đủ thuộc tính: vẫn hiển thị giá flash tham chiếu (nếu có).
                var firstFlash = null;
                for (var i = 0; i < variants.length; i++) {
                    if (variants[i].flash_price != null && variants[i].flash_remaining > 0) { firstFlash = variants[i]; break; }
                }
                if (firstFlash) {
                    var eff = firstFlash.flash_price;
                    if (flashCardEl) flashCardEl.style.display = '';
                    flashPriceRow.style.display = '';
                    flashPriceEl.textContent = formatMoneyVn(eff);
                    if (flashOldPriceEl) {
                        flashOldPriceEl.style.display = '';
                        flashOldPriceEl.innerHTML = '<s>' + formatMoneyVn(firstFlash.price) + '</s>';
                    }
                    if (flashDiscountEl) {
                        var pct = firstFlash.price > 0 ? Math.round((1 - (eff / firstFlash.price)) * 100) : 0;
                        pct = Math.max(0, Math.min(99, pct));
                        flashDiscountEl.style.display = pct > 0 ? '' : 'none';
                        flashDiscountEl.textContent = '-' + pct + '%';
                    }
                    if (priceWrapEl) priceWrapEl.style.display = 'none';
                } else {
                    flashPriceRow.style.display = 'none';
                    if (flashCardEl) flashCardEl.style.display = 'none';
                    if (priceWrapEl) priceWrapEl.style.display = '';
                }
            }
            if (qtyInput) {
                qtyInput.max = 999;
                qtyInput.readOnly = true;
                qtyInput.setAttribute('tabindex', '-1');
            }
            if (qtyMinusBtn) qtyMinusBtn.disabled = true;
            if (qtyPlusBtn) qtyPlusBtn.disabled = true;
            if (stockLabel) {
                if (selectedColor && (!sizeAttrName || !selectedSize)) {
                    var sumLabel = 0;
                    variants.forEach(function(vv) {
                        if ((vv.attr[colorAttrName] || '') !== selectedColor) return;
                        var effStock = (vv.flash_remaining != null && vv.flash_remaining > 0) ? Math.min(vv.stock || 0, vv.flash_remaining) : (vv.stock || 0);
                        sumLabel += effStock;
                    });
                    stockLabel.textContent = (sumLabel > 0 ? sumLabel.toLocaleString('vi-VN') + ' SẢN PHẨM CÓ SẴN' : 'HẾT HÀNG');
                } else {
                    stockLabel.textContent = totalStock > 0 ? 'CÒN HÀNG' : 'HẾT HÀNG';
                }
                stockLabel.classList.add('product-stock-status');
            }
            if (mainImg) {
                // Khi mới chọn Màu nhưng chưa chọn đủ (ví dụ: còn Size) => lấy ảnh của variant đầu tiên cùng màu có ảnh.
                var preferredImg = defaultImage || (variants[0] && variants[0].image) || '';
                if (selectedColor && (!sizeAttrName || !selectedSize)) {
                    var sameColorV = findFirstVariantWithSameColorAndImage(selectedColor);
                    if (sameColorV && sameColorV.image) preferredImg = sameColorV.image;
                }
                mainImg.src = preferredImg;
                mainImg.style.display = mainImg.src ? '' : 'none';
            }
            setGalleryActive(mainImg ? mainImg.src : defaultImage);
        }
        updateStockNotifyVisibility();
    }

    function syncQuantityToHidden() {
        var qtyInput = document.getElementById('product-quantity-input');
        var hiddenQty = document.getElementById('add-to-cart-quantity');
        if (qtyInput && hiddenQty) {
            var val = parseInt(qtyInput.value, 10) || 1;
            var max = parseInt(qtyInput.getAttribute('max'), 10) || 999;
            val = Math.min(Math.max(1, val), max);
            qtyInput.value = val;
            hiddenQty.value = val;
        }
        // Khi chọn đủ variant và đạt max -> hiện thông báo đỏ.
        if (typeof updateQtyMaxWarning === 'function') updateQtyMaxWarning();
    }

    function setGalleryActive(src) {
        if (!src) return;
        var path = (src.match(/\/images\/.*/) || [src])[0];
        if (path.indexOf('http') === 0) path = path.replace(/^https?:\/\/[^/]+/, '');
        document.querySelectorAll('#product-gallery .product-gallery-thumb').forEach(function(thumb) {
            thumb.classList.toggle('active', thumb.getAttribute('data-src') === path || thumb.getAttribute('data-src') === src);
        });
    }

    document.querySelectorAll('.variant-option').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (this.disabled) return;
            var attr = this.getAttribute('data-attribute');
            var val = this.getAttribute('data-value');
            selection[attr] = selection[attr] === val ? null : val;
            updateState();
        });
    });
    form.addEventListener('submit', function() {
        if (btnAdd.disabled) return false;
        syncQuantityToHidden();
    });
    var qtyInput = document.getElementById('product-quantity-input');
    var qtyMinus = document.querySelector('.product-qty-minus');
    var qtyPlus = document.querySelector('.product-qty-plus');
    if (qtyInput) {
        qtyInput.addEventListener('change', syncQuantityToHidden);
        qtyInput.addEventListener('input', function() { syncQuantityToHidden(); });
    }
    if (qtyMinus) qtyMinus.addEventListener('click', function() {
        if (this.disabled || !qtyInput) return;
        var v = Math.max(1, (parseInt(qtyInput.value, 10) || 1) - 1);
        qtyInput.value = v;
        syncQuantityToHidden();
    });
    if (qtyPlus) qtyPlus.addEventListener('click', function() {
        if (this.disabled || !qtyInput) return;
        var max = parseInt(qtyInput.getAttribute('max'), 10) || 999;
        var v = Math.min(max, (parseInt(qtyInput.value, 10) || 1) + 1);
        qtyInput.value = v;
        syncQuantityToHidden();
    });
    syncQuantityToHidden();
    var galleryTrack = document.getElementById('product-gallery');
    if (galleryTrack) {
        galleryTrack.addEventListener('click', function(e) {
            var thumb = e.target.closest('.product-gallery-thumb');
            if (!thumb) return;
            var src = thumb.getAttribute('data-src');
            if (mainImg && src) {
                mainImg.src = src;
                mainImg.style.display = '';
            }
            document.querySelectorAll('#product-gallery .product-gallery-thumb').forEach(function(t) { t.classList.remove('active'); });
            thumb.classList.add('active');
            thumb.blur();
        });
        var prevBtn = document.querySelector('.product-gallery-prev');
        var nextBtn = document.querySelector('.product-gallery-next');
        var thumbEl = galleryTrack.querySelector('.product-gallery-thumb-item');
        var scrollStep = thumbEl ? (thumbEl.offsetWidth + 8) : 88;
        function updateArrowState() {
            if (!prevBtn || !nextBtn) return;
            var atStart = galleryTrack.scrollLeft <= 1;
            var atEnd = galleryTrack.scrollLeft >= galleryTrack.scrollWidth - galleryTrack.clientWidth - 1;
            prevBtn.style.opacity = atStart ? '0.4' : '1';
            prevBtn.style.pointerEvents = atStart ? 'none' : 'auto';
            nextBtn.style.opacity = atEnd ? '0.4' : '1';
            nextBtn.style.pointerEvents = atEnd ? 'none' : 'auto';
        }
        function smoothScrollGallery(direction) {
            var start = galleryTrack.scrollLeft;
            var dist = direction === 'prev' ? -Math.min(scrollStep, start) : Math.min(scrollStep, galleryTrack.scrollWidth - galleryTrack.clientWidth - start);
            if (dist === 0) { updateArrowState(); return; }
            var startTime = null;
            function stepAnim(ts) {
                if (!startTime) startTime = ts;
                var elapsed = ts - startTime;
                var duration = 280;
                var t = Math.min(elapsed / duration, 1);
                t = 1 - Math.pow(1 - t, 2);
                galleryTrack.scrollLeft = start + dist * t;
                if (elapsed < duration) requestAnimationFrame(stepAnim);
                else updateArrowState();
            }
            requestAnimationFrame(stepAnim);
        }
        if (prevBtn) prevBtn.addEventListener('click', function() { smoothScrollGallery('prev'); });
        if (nextBtn) nextBtn.addEventListener('click', function() { smoothScrollGallery('next'); });
        galleryTrack.addEventListener('scroll', updateArrowState);
updateArrowState();
        setTimeout(updateArrowState, 100);
    }
    updateState();
    if (flashSaleEndTime) {
        function setCountdownNum(boxEl, val) {
            if (boxEl) boxEl.textContent = val < 10 ? '0' + val : '' + val;
        }
        var hEl = document.getElementById('flash-sale-h');
        var mEl = document.getElementById('flash-sale-m');
        var sEl = document.getElementById('flash-sale-s');
        if (hEl && mEl && sEl) {
            var currentEndTime = new Date(flashSaleEndTime).getTime();
            var countdownTimerId = null;
            var flashSaleApiUrl = @json(route('api.flash-sale'));
            function runCountdown() {
                var now = new Date().getTime();
                var distance = currentEndTime - now;
                if (distance <= 0) {
                    if (countdownTimerId) { clearInterval(countdownTimerId); countdownTimerId = null; }
                    fetch(flashSaleApiUrl).then(function(r) { return r.json(); }).then(function(data) {
                        if (data.current) {
                            currentEndTime = new Date(data.current.end_time).getTime();
                        } else {
                            setCountdownNum(hEl, 0);
                            setCountdownNum(mEl, 0);
                            setCountdownNum(sEl, 0);
                        }
                        countdownTimerId = setInterval(runCountdown, 1000);
                    }).catch(function() {
                        countdownTimerId = setInterval(runCountdown, 1000);
                    });
                    return;
                }
                var hours = Math.floor((distance / (1000 * 60 * 60)) % 24);
                var minutes = Math.floor((distance / (1000 * 60)) % 60);
                var seconds = Math.floor((distance / 1000) % 60);
                setCountdownNum(hEl, hours);
                setCountdownNum(mEl, minutes);
                setCountdownNum(sEl, seconds);
            }
            runCountdown();
            countdownTimerId = setInterval(runCountdown, 1000);
        }
    }
    })();
</script>
<style>
/* Đúng 4 ảnh trên 1 hàng: track 344px, ảnh thứ 5+ ẩn hẳn */
#product-gallery-wrapper { min-height: 88px; position: relative; }
#product-gallery {
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    -ms-overflow-style: none;
    scroll-behavior: smooth;
}
#product-gallery::-webkit-scrollbar { display: none; }
#product-gallery .product-gallery-thumb-btn,
#product-gallery button.product-gallery-thumb { border: 2px solid #dee2e6 !important; outline: none !important; -webkit-appearance: none; appearance: none; }
#product-gallery .product-gallery-thumb-btn:focus,
#product-gallery .product-gallery-thumb-btn:focus-visible,
#product-gallery button.product-gallery-thumb:focus,
#product-gallery button.product-gallery-thumb:focus-visible { outline: none !important; border: 2px solid #dc3545 !important; border-color: #dc3545 !important; box-shadow: 0 0 0 2px rgba(220,53,69,0.5); }
#product-gallery .product-gallery-thumb-btn.active,
#product-gallery button.product-gallery-thumb.active { border: 2px solid #dc3545 !important; border-color: #dc3545 !important; box-shadow: 0 0 0 2px rgba(220,53,69,0.5); }
.product-gallery-thumb-item:last-child { margin-right: 0; }
.product-gallery-arrow { transition: opacity 0.2s; }
.product-gallery-arrow:focus,
.product-gallery-arrow:focus-visible { outline: none !important; box-shadow: 0 0 0 2px rgba(220,53,69,0.5); }
@media (max-width: 500px) {
    #product-gallery-wrapper { width: 100% !important; }
    #product-gallery { width: calc(100% - 80px) !important; min-width: 0 !important; max-width: none !important; }
}
</style>
@else
<script>window.productHasVariants = false;</script>
@endif

{{-- Chọn số lượng: nút +/- và đồng bộ (chạy cả khi không có biến thể) --}}
<style>
.product-quantity-wrap .input-group-prepend .btn,
.product-quantity-wrap .input-group-append .btn { padding: 0.25rem 0.5rem; font-weight: 600; }
.product-quantity-wrap .form-control.product-quantity-input {
    border: 1px solid #6c757d;
    border-left: none;
    border-right: none;
}
.product-quantity-wrap .input-group-prepend .btn { border-right: none; }
.product-quantity-wrap .input-group-append .btn { border-left: none; }
.product-quantity-wrap .product-qty-minus:not(:disabled):hover,
.product-quantity-wrap .product-qty-minus:not(:disabled):focus,
.product-quantity-wrap .product-qty-plus:not(:disabled):hover,
.product-quantity-wrap .product-qty-plus:not(:disabled):focus {
    color: #dc3545 !important;
    border-color: #dc3545 !important;
    background-color: rgba(220, 53, 69, 0.08);
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
.product-quantity-wrap .product-qty-minus:not(:disabled):focus,
.product-quantity-wrap .product-qty-plus:not(:disabled):focus {
    outline: none;
}
#product-stock-label.product-stock-status {
    font-weight: 700;
    color: #b21f2d !important;
    text-transform: uppercase;
}
</style>
@auth
<script>
(function() {
    if (window.productHasVariants) return;
    var qtyInput = document.getElementById('product-quantity-input');
    var hiddenQty = document.getElementById('add-to-cart-quantity');
    if (!qtyInput || !hiddenQty) return;
    function sync() {
        var val = parseInt(qtyInput.value, 10) || 1;
        var max = parseInt(qtyInput.getAttribute('max'), 10) || 999;
        val = Math.min(Math.max(1, val), max);
        qtyInput.value = val;
        hiddenQty.value = val;
    }
    qtyInput.addEventListener('change', sync);
    qtyInput.addEventListener('input', sync);
    document.querySelector('.product-qty-minus') && document.querySelector('.product-qty-minus').addEventListener('click', function() {
        qtyInput.value = Math.max(1, (parseInt(qtyInput.value, 10) || 1) - 1);
        sync();
    });
    document.querySelector('.product-qty-plus') && document.querySelector('.product-qty-plus').addEventListener('click', function() {
        var max = parseInt(qtyInput.getAttribute('max'), 10) || 999;
        qtyInput.value = Math.min(max, (parseInt(qtyInput.value, 10) || 1) + 1);
        sync();
    });
    document.getElementById('add-to-cart-form') && document.getElementById('add-to-cart-form').addEventListener('submit', sync);
    sync();
})();
</script>
@endauth
@endsection
