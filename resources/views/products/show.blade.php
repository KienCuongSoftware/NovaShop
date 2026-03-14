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
                            <span class="text-muted mr-2" style="text-decoration: line-through;" style="font-size: 1rem;">{{ number_format($product->old_price, 0, ',', '.') }}₫</span>
                        @endif
                        <span class="text-danger font-weight-bold" style="font-size: 1.5rem;">{{ number_format($product->price, 0, ',', '.') }}₫</span>
                    </div>

                    @if($product->quantity !== null)
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
                        <form action="{{ route('cart.add') }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-danger">Thêm vào giỏ</button>
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
@endsection
