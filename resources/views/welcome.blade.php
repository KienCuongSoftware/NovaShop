@extends('layouts.user')

@section('title', 'NovaShop')

@section('content')
<div class="mb-4">
    <h1 class="mb-4">Chào mừng bạn đến với NovaShop</h1>
    @if(isset($q) && $q !== '')
        <p class="text-muted">Kết quả tìm kiếm: <strong>{{ $q }}</strong></p>
    @endif
</div>

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
