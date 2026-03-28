@extends('layouts.user')

@section('title', 'Wishlist chia sẻ - NovaShop')

@section('content')
<div class="page-header mb-4">
    <h2 class="mb-0">Wishlist chia sẻ</h2>
    <div class="text-muted small mt-2">
        Link: <a href="{{ route('share.wishlist.show', ['token' => $share->token]) }}" target="_blank" rel="noopener">{{ route('share.wishlist.show', ['token' => $share->token]) }}</a>
    </div>
    <div class="mt-3">
        <a href="{{ route('welcome') }}" class="btn btn-outline-secondary btn-sm">← Về trang chủ</a>
    </div>
</div>

@if($products->isEmpty())
    @include('partials.empty-state', [
        'type' => 'wishlist_share',
        'title' => 'Không có sản phẩm trong wishlist',
        'message' => 'Danh sách này hiện đang rỗng.',
        'actionUrl' => route('welcome'),
        'actionLabel' => 'Mua sắm ngay',
    ])
@else
    <div class="row">
        @foreach($products as $p)
            @if(!$p) @continue @endif
            <div class="col-6 col-md-4 col-lg-3 mb-4">
                <div class="card h-100 shadow-sm">
                    <a href="{{ route('products.show', $p) }}">
                        @if($p->image)
                            <img src="/images/products/{{ basename($p->image) }}" class="card-img-top" alt="{{ $p->name }}" style="height: 180px; object-fit: contain;">
                        @else
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 180px;">—</div>
                        @endif
                    </a>
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title">
                            <a href="{{ route('products.show', $p) }}" class="text-dark">{{ \Illuminate\Support\Str::limit($p->name, 48) }}</a>
                        </h6>
                        <div class="mt-auto">
                            <div class="text-danger font-weight-bold mb-2">{{ number_format($p->effective_price, 0, ',', '.') }}₫</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection

