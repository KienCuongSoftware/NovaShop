@extends('layouts.user')

@section('title', $product->name . ' - NovaShop')

@section('content')
@php
    $attributeOptions = $product->getAttributeOptionsForFrontend();
    $variantsPayload = $product->variants->map(function ($v) {
        return [
            'id' => $v->id,
            'attr' => $v->attribute_map,
            'price' => (float) $v->price,
            'stock' => (int) $v->stock,
            'image' => $v->main_image_url,
        ];
    })->values()->all();
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
                    <div class="product-detail-img bg-light rounded overflow-hidden mb-2" style="min-height: 280px;">
                        <img id="main-product-image" src="{{ $defaultImage ?? '' }}" alt="{{ $product->name }}" class="img-fluid w-100" style="object-fit: contain; max-height: 400px;" @if(!$defaultImage) style="display:none;" @endif>
                        @if(!$defaultImage && !$hasVariants)
                        <div class="d-flex align-items-center justify-content-center h-100 text-muted py-5">Không có ảnh</div>
                        @endif
                    </div>
                    @if(!empty($galleryImages))
                    {{-- Chỉ hiển thị 4 ảnh, ảnh còn lại ẩn, dùng mũi tên để xem --}}
                    <div class="product-gallery-wrapper position-relative" id="product-gallery-wrapper" style="width: 424px; max-width: 100%; box-sizing: border-box; overflow: hidden;">
                        <button type="button" class="product-gallery-arrow product-gallery-prev border-0 rounded-circle shadow bg-danger text-white" aria-label="Ảnh trước" style="width: 40px; height: 40px; position: absolute; left: 0; top: 50%; transform: translateY(-50%); z-index: 10; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; line-height: 1;">‹</button>
                        <div class="product-gallery-track d-flex flex-nowrap" id="product-gallery" style="scroll-behavior: smooth; overflow-x: auto; width: 344px; min-width: 344px; max-width: 344px; margin: 0 40px; box-sizing: border-box;">
                            @foreach($galleryImages as $gurl)
                            <button type="button" class="product-gallery-thumb border rounded flex-shrink-0 product-gallery-thumb-item {{ $loop->first ? 'active' : '' }}" data-src="{{ $gurl }}" style="width: 80px; height: 80px; overflow: hidden; background: #fff; margin-right: 8px; flex-shrink: 0;">
                                <img src="{{ $gurl }}" alt="" class="w-100 h-100" style="object-fit: cover;">
                            </button>
                            @endforeach
                        </div>
                        <button type="button" class="product-gallery-arrow product-gallery-next border-0 rounded-circle shadow bg-danger text-white" aria-label="Ảnh sau" style="width: 40px; height: 40px; position: absolute; right: 0; top: 50%; transform: translateY(-50%); z-index: 10; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; line-height: 1;">›</button>
                    </div>
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

                    <div class="mb-3">
                        @if($product->old_price !== null)
                            <span class="text-muted mr-2" style="text-decoration: line-through; font-size: 1rem;">{{ number_format($product->old_price, 0, ',', '.') }}₫</span>
                        @endif
                        <span class="text-danger font-weight-bold" id="product-price" style="font-size: 1.5rem;">{{ number_format($hasVariants ? ($product->variants->first()->price ?? $product->price) : $product->price, 0, ',', '.') }}₫</span>
                    </div>

                    @if($hasVariants)
                    <div class="mb-3" id="variant-options-wrap">
                        @foreach($attributeOptions as $attrName => $values)
                        <p class="mb-2 font-weight-bold">{{ $attrName }}</p>
                        <div class="d-flex flex-wrap mb-3 variant-buttons" data-attribute="{{ $attrName }}">
                            @foreach($values as $val)
                            <button type="button" class="btn variant-option" data-attribute="{{ $attrName }}" data-value="{{ $val }}">{{ $val }}</button>
                            @endforeach
                        </div>
                        @endforeach
                        <p class="text-muted small mb-2" id="variant-stock">Chọn đầy đủ thuộc tính để xem tồn kho.</p>
                    </div>
                    @elseif($product->quantity !== null)
                    <p class="text-muted small mb-3">Còn lại: <strong>{{ number_format($product->quantity, 0, ',', '.') }}</strong> sản phẩm</p>
                    @endif

                    <div class="mt-4">
                        @auth
                        <form action="{{ route('cart.add') }}" method="POST" class="d-inline" id="add-to-cart-form">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            @if($hasVariants)
                            <input type="hidden" name="product_variant_id" value="" id="product_variant_id">
                            @endif
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-danger" id="btn-add-cart" @if($hasVariants) disabled @endif>Thêm vào giỏ</button>
                        </form>
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
        </div>
    </div>
</div>

@if($hasVariants)
<style>
.variant-buttons .variant-option {
    min-width: 52px;
    padding: 0.5rem 1rem;
    font-size: 1rem;
    border-radius: 0.5rem;
    border: 1px solid #ced4da;
    background: #f1f3f5;
    color: #333;
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}
.variant-buttons .variant-option:hover:not(:disabled) {
    border-color: #b02a37;
    background: #b02a37;
    color: #fff;
}
.variant-buttons .variant-option:focus {
    outline: none;
    border-color: #dc3545;
    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.25);
}
.variant-buttons .variant-option.active {
    border-color: #dc3545;
    background: #dc3545;
    color: #fff;
}
.variant-buttons .variant-option:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
<script>
(function() {
    var variants = @json($variantsPayload);
    var defaultImage = @json($defaultImage);
    var firstAttrName = @json($hasVariants && !empty($attributeOptions) ? array_key_first($attributeOptions) : null);
    var mainImg = document.getElementById('main-product-image');
    var priceEl = document.getElementById('product-price');
    var variantInput = document.getElementById('product_variant_id');
    var stockEl = document.getElementById('variant-stock');
    var btnAdd = document.getElementById('btn-add-cart');
    var form = document.getElementById('add-to-cart-form');
    var selection = {};

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
            if ((v.attr[firstAttrName] || '') !== colorVal) continue;
            if (v.image && v.image !== defaultImage) return v;
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
                available[v.attr[attrName]] = true;
            }
        });
        return available;
    }

    function updateState() {
        var v = findVariant();
        document.querySelectorAll('.variant-option').forEach(function(btn) {
            var attr = btn.getAttribute('data-attribute');
            var val = btn.getAttribute('data-value');
            btn.classList.toggle('active', selection[attr] === val);
            var available = getAvailableValues(attr);
            btn.disabled = Object.keys(available).length > 0 && !available[val];
        });
        if (v) {
            variantInput.value = v.id;
            priceEl.textContent = v.price.toLocaleString('vi-VN') + '₫';
            stockEl.textContent = 'Còn lại: ' + v.stock + ' sản phẩm';
            btnAdd.disabled = v.stock < 1;
            var displayImage = v.image;
            if (!displayImage || displayImage === defaultImage) {
                var colorVal = firstAttrName ? (selection[firstAttrName] || null) : null;
                var sameColorV = findFirstVariantWithSameColorAndImage(colorVal);
                if (sameColorV && sameColorV.image) displayImage = sameColorV.image;
                else if (!displayImage) displayImage = defaultImage || (variants[0] && variants[0].image) || '';
            }
            if (mainImg) {
                mainImg.src = displayImage || '';
                mainImg.style.display = displayImage ? '' : 'none';
            }
            setGalleryActive(displayImage);
        } else {
            variantInput.value = '';
            var need = [];
            @foreach($attributeOptions as $attrName => $values)
            if (!selection['{{ $attrName }}']) need.push('{{ $attrName }}');
            @endforeach
            priceEl.textContent = @json($product->variants->first()->price ?? $product->price).toLocaleString('vi-VN') + '₫';
            stockEl.textContent = need.length ? 'Chọn ' + need.join(', ') + '.' : 'Chọn đầy đủ thuộc tính để xem tồn kho.';
            btnAdd.disabled = true;
            var partial = findFirstVariantMatchingSelection();
            if (mainImg) {
                mainImg.src = (partial && partial.image) ? partial.image : (defaultImage || (variants[0] && variants[0].image) || '');
                mainImg.style.display = mainImg.src ? '' : 'none';
            }
            setGalleryActive(partial && partial.image ? partial.image : defaultImage);
        }
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
    });
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
        if (prevBtn) prevBtn.addEventListener('click', function() { galleryTrack.scrollLeft -= scrollStep; updateArrowState(); });
        if (nextBtn) nextBtn.addEventListener('click', function() { galleryTrack.scrollLeft += scrollStep; updateArrowState(); });
        galleryTrack.addEventListener('scroll', updateArrowState);
        updateArrowState();
        setTimeout(updateArrowState, 100);
    }
    updateState();
})();
</script>
<style>
/* Đúng 4 ảnh trên 1 hàng: track 344px, ảnh thứ 5+ ẩn hẳn */
#product-gallery-wrapper { min-height: 88px; position: relative; }
#product-gallery {
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    -ms-overflow-style: none;
}
#product-gallery::-webkit-scrollbar { display: none; }
.product-gallery-thumb.active { border-color: #dc3545 !important; box-shadow: 0 0 0 2px rgba(220,53,69,0.3); }
.product-gallery-thumb-item:last-child { margin-right: 0; }
.product-gallery-arrow { transition: opacity 0.2s; }
@media (max-width: 500px) {
    #product-gallery-wrapper { width: 100% !important; }
    #product-gallery { width: calc(100% - 80px) !important; min-width: 0 !important; max-width: none !important; }
}
</style>
@endif
@endsection
