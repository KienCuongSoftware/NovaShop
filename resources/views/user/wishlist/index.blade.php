@extends('layouts.user')

@section('title', 'Yêu thích - NovaShop')

@section('content')
<div class="page-header mb-4">
    <h2>Yêu thích</h2>
    <a href="{{ route('welcome') }}" class="btn btn-outline-secondary">← Tiếp tục mua sắm</a>
</div>

@if($items->isEmpty())
@include('partials.empty-state', [
    'type' => 'wishlist',
    'title' => 'Chưa có sản phẩm yêu thích',
    'message' => 'Vào trang chi tiết sản phẩm và bấm « Yêu thích ». Trên máy dev có thể chạy seeder NovaShopFeaturesSampleSeeder để tạo dữ liệu mẫu cho mọi tài khoản.',
    'actionUrl' => route('welcome'),
    'actionLabel' => 'Mua sắm ngay',
])
@else
<div class="row">
    @foreach($items as $row)
    @php $p = $row->product; @endphp
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
                <h6 class="card-title"><a href="{{ route('products.show', $p) }}" class="text-dark">{{ Str::limit($p->name, 48) }}</a></h6>
                <div class="mt-auto">
                    <div class="text-danger font-weight-bold mb-2">{{ number_format($p->effective_price, 0, ',', '.') }}₫</div>
                    <form action="{{ route('wishlist.remove', $p) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa khỏi yêu thích?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
<div class="d-flex justify-content-center">{{ $items->links() }}</div>
@endif
@endsection
