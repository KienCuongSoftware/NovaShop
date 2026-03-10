@extends('layouts.user')

@section('title', 'MyShop')

@section('content')
<div class="mb-4">
    <h1 class="mb-4">Welcome to Our Store</h1>
</div>

<div class="card-deck">
    @forelse ($products as $product)
    <div class="card mb-4">
        <div class="card-body text-center">
            @if($product->image)
                <img src="/images/products/{{ basename($product->image) }}" alt="{{ $product->name }}" class="img-fluid mb-2" style="max-height: 120px; object-fit: contain;" loading="lazy">
            @endif
            <h5 class="card-title">{{ $product->name }}</h5>
            <p class="card-text">{{ Str::limit($product->description, 80) }}</p>
            <p class="card-text">Quantity: {{ $product->quantity }}</p>
            <p class="card-text">Price: {{ number_format($product->price, 0, ',', '.') }}₫</p>
            <p class="card-text">Category: {{ optional($product->category)->name }}</p>
            @auth
            <a href="{{ route('products.show', $product->id) }}" class="btn btn-primary">View Details</a>
            @else
            <a href="{{ route('login') }}" class="btn btn-primary">Login to view</a>
            @endauth
        </div>
    </div>
    @empty
    <p class="text-muted">Chưa có sản phẩm nào.</p>
    @endforelse
</div>

<!-- Hiển thị liên kết phân trang -->
<div class="d-flex justify-content-center mt-3">
    {{ $products->links() }}
</div>
@endsection
