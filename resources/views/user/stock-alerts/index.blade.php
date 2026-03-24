@extends('layouts.user')

@section('title', 'Thông báo có hàng - NovaShop')

@section('content')
<style>
    .stock-alert-row {
        gap: 0.75rem 0;
    }
    .stock-alert-text {
        min-width: 0;
    }
    .stock-alert-view-btn {
        border-radius: 0.5rem !important;
        flex-shrink: 0;
        margin-left: 1.75rem;
        padding-left: 1rem;
        padding-right: 1rem;
    }
    @media (max-width: 575px) {
        .stock-alert-view-btn {
            margin-left: auto;
            margin-top: 0.5rem;
        }
    }
</style>
<div class="page-header mb-4">
    <h2>Thông báo có hàng lại</h2>
    <p class="text-muted small mb-0">Các sản phẩm bạn đăng ký đã có tồn kho (đã gửi email).</p>
</div>

@if($rows->isEmpty())
@include('partials.empty-state', [
    'type' => 'bell',
    'title' => 'Chưa có thông báo có hàng',
    'message' => 'Đăng ký « Báo khi có hàng » trên SP hết hàng; khi nhập kho lại bạn sẽ nhận email và dòng thông báo tại đây. Seeder mẫu tạo một thông báo demo cho từng user.',
    'actionUrl' => route('welcome'),
    'actionLabel' => 'Về trang chủ',
])
@else
<div class="list-group">
    @foreach($rows as $row)
    <div class="list-group-item d-flex align-items-center flex-wrap stock-alert-row">
        @if($row->product?->image)
            <img src="/images/products/{{ basename($row->product->image) }}" alt="" class="rounded mr-3" style="width: 56px; height: 56px; object-fit: cover;">
        @endif
        <div class="flex-grow-1 stock-alert-text pr-md-2">
            <a href="{{ route('products.show', $row->product) }}" class="font-weight-bold text-dark">{{ $row->product->name }}</a>
            @if($row->product_variant_id && $row->productVariant)
                <div class="small text-muted">{{ $row->productVariant->display_name }}</div>
            @endif
            <div class="small text-muted">Thông báo lúc {{ $row->notified_at->format('d/m/Y H:i') }}</div>
        </div>
        <a href="{{ route('products.show', $row->product) }}" class="btn btn-sm btn-danger stock-alert-view-btn">Xem sản phẩm</a>
    </div>
    @endforeach
</div>
<div class="d-flex justify-content-center mt-3">{{ $rows->links() }}</div>
@endif
@endsection
