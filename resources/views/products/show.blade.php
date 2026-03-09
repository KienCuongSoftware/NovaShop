@extends('layouts.app')

@section('title', 'Chi tiết sản phẩm')

@section('content')
<div class="page-header">
    <h2>Chi tiết sản phẩm</h2>
    <a class="btn btn-primary" href="{{ route('products.index') }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Tên:</dt>
            <dd class="col-sm-9">{{ $product->name }}</dd>

            <dt class="col-sm-3">Danh mục:</dt>
            <dd class="col-sm-9">{{ $product->category->name ?? '—' }}</dd>

            <dt class="col-sm-3">Mô tả:</dt>
            <dd class="col-sm-9">{{ $product->description ?: '—' }}</dd>

            <dt class="col-sm-3">Giá:</dt>
            <dd class="col-sm-9">{{ number_format($product->price, 0, ',', '.') }}₫</dd>

            <dt class="col-sm-3">Số lượng:</dt>
            <dd class="col-sm-9">{{ $product->quantity }}</dd>

            <dt class="col-sm-3">Trạng thái:</dt>
            <dd class="col-sm-9">{{ $product->is_active ? 'Đang bán' : 'Ẩn' }}</dd>

            <dt class="col-sm-3">Hình ảnh:</dt>
            <dd class="col-sm-9">
                @if($product->image)
                    <img src="/images/products/{{ basename($product->image) }}" alt="{{ $product->name }}" class="img-thumbnail" style="max-width: 200px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                    <span class="text-muted" style="display:none;">Không tải được ảnh</span>
                @else
                    <span class="text-muted">Không có ảnh</span>
                @endif
            </dd>
        </dl>
    </div>
</div>
@endsection
