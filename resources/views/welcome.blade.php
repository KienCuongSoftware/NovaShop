@extends('layouts.user')

@section('title', 'NovaShop')

@section('content')
<div class="mb-4">
    <h1 class="mb-4">Chào mừng bạn đến với NovaShop</h1>
</div>

<div class="row">
    @forelse ($products as $product)
    <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
        <div class="card h-100">
            <div class="card-body text-center d-flex flex-column">
                @if($product->image)
                    <img src="/images/products/{{ basename($product->image) }}" alt="{{ $product->name }}" class="img-fluid mb-2 mx-auto" style="max-height: 120px; width: auto; object-fit: contain;" loading="lazy">
                @endif
                <h5 class="card-title">{{ $product->name }}</h5>
                <p class="card-text small text-muted flex-grow-1">{{ Str::limit($product->description, 80) }}</p>
                <p class="card-text mb-1"><strong>{{ number_format($product->price, 0, ',', '.') }}₫</strong></p>
                <p class="card-text small text-muted mb-2">Danh mục: {{ optional($product->category)->name }}</p>
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
