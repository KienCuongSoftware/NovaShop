@extends('layouts.user')

@section('title', $product->name . ' - NovaShop')

@section('content')
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
                    <div class="product-detail-img bg-light rounded overflow-hidden" style="min-height: 280px;">
                        @if($product->image)
                            <img src="/images/products/{{ basename($product->image) }}" alt="{{ $product->name }}" class="img-fluid w-100" style="object-fit: contain; max-height: 400px;">
                        @else
                            <div class="d-flex align-items-center justify-content-center h-100 text-muted py-5">Không có ảnh</div>
                        @endif
                    </div>
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
                        <span class="text-danger font-weight-bold" style="font-size: 1.5rem;">{{ number_format($product->price, 0, ',', '.') }}₫</span>
                    </div>

                    @php
                        $hasSizes = count($product->distinct_sizes) > 0;
                        $hasColors = count($product->distinct_colors) > 0;
                        $variantsJson = $product->variants->map(fn ($v) => [
                            'id' => $v->id,
                            'size' => $v->size,
                            'color' => $v->color,
                            'quantity' => $v->quantity,
                        ])->values()->toJson();
                    @endphp

                    @if($product->hasVariants())
                    <div class="mb-3">
                        @if($hasSizes)
                        <p class="mb-2 font-weight-bold">Size:</p>
                        <div class="d-flex flex-wrap mb-3 variant-buttons">
                            @foreach($product->distinct_sizes as $size)
                            <button type="button" class="btn variant-option size-option" data-size="{{ $size }}">{{ $size }}</button>
                            @endforeach
                        </div>
                        @endif
                        @if($hasColors)
                        <p class="mb-2 font-weight-bold">Màu sắc:</p>
                        <div class="d-flex flex-wrap mb-3 variant-buttons">
                            @foreach($product->distinct_colors as $color)
                            <button type="button" class="btn variant-option color-option" data-color="{{ $color }}">{{ $color }}</button>
                            @endforeach
                        </div>
                        @endif
                        <p class="text-muted small mb-2" id="variant-stock">Chọn Size/Màu để xem tồn kho.</p>
                    </div>
                    @elseif($product->quantity !== null)
                    <p class="text-muted small mb-3">Còn lại: <strong>{{ number_format($product->quantity, 0, ',', '.') }}</strong> sản phẩm</p>
                    @endif

                    @if($product->description)
                    <div class="border-top pt-3 mt-3">
                        <h6 class="font-weight-bold text-dark mb-2">Mô tả</h6>
                        <p class="text-secondary mb-0" style="white-space: pre-line;">{{ $product->description }}</p>
                    </div>
                    @endif

                    <div class="mt-4">
                        @auth
                        <form action="{{ route('cart.add') }}" method="POST" class="d-inline" id="add-to-cart-form">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            @if($product->hasVariants())
                            <input type="hidden" name="product_variant_id" value="" id="product_variant_id">
                            @endif
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-danger" id="btn-add-cart" @if($product->hasVariants()) disabled @endif>Thêm vào giỏ</button>
                        </form>
                        @else
                        <a href="{{ route('login') }}" class="btn btn-danger">Đăng nhập để thêm vào giỏ</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($product->hasVariants())
<style>
.variant-buttons .variant-option {
    min-width: 52px;
    padding: 0.5rem 1rem;
    font-size: 1rem;
    border-radius: 0.5rem;
    border: 1px solid #dee2e6;
    background: #fff;
    color: #333;
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}
.variant-buttons .variant-option:hover {
    border-color: #adb5bd;
    background: #f8f9fa;
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
.variant-buttons .variant-option.active:focus {
    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.25);
}
</style>
<script>
(function() {
    var variants = {!! $variantsJson !!};
    var sizeOpts = document.querySelectorAll('.size-option');
    var colorOpts = document.querySelectorAll('.color-option');
    var variantInput = document.getElementById('product_variant_id');
    var stockEl = document.getElementById('variant-stock');
    var btnAdd = document.getElementById('btn-add-cart');
    var form = document.getElementById('add-to-cart-form');
    var selectedSize = null;
    var selectedColor = null;

    function findVariant() {
        for (var i = 0; i < variants.length; i++) {
            var v = variants[i];
            var matchSize = !selectedSize ? !v.size : v.size === selectedSize;
            var matchColor = !selectedColor ? !v.color : v.color === selectedColor;
            if (matchSize && matchColor) return v;
        }
        return null;
    }

    function updateState() {
        var v = findVariant();
        [].forEach.call(sizeOpts, function(btn) {
            btn.classList.toggle('active', btn.dataset.size === selectedSize);
        });
        [].forEach.call(colorOpts, function(btn) {
            btn.classList.toggle('active', btn.dataset.color === selectedColor);
        });
        if (v) {
            variantInput.value = v.id;
            stockEl.textContent = 'Còn lại: ' + v.quantity + ' sản phẩm';
            btnAdd.disabled = v.quantity < 1;
        } else {
            variantInput.value = '';
            var need = [];
            if (sizeOpts.length && !selectedSize) need.push('Size');
            if (colorOpts.length && !selectedColor) need.push('Màu');
            stockEl.textContent = need.length ? 'Chọn ' + need.join(' và ') + '.' : 'Chọn Size/Màu để xem tồn kho.';
            btnAdd.disabled = true;
        }
    }

    sizeOpts.forEach(function(btn) {
        btn.addEventListener('click', function() {
            selectedSize = btn.dataset.size;
            updateState();
        });
    });
    colorOpts.forEach(function(btn) {
        btn.addEventListener('click', function() {
            selectedColor = btn.dataset.color;
            updateState();
        });
    });
    form.addEventListener('submit', function() {
        if (btnAdd.disabled) return false;
    });
})();
</script>
@endif
@endsection
