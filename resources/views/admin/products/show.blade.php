@extends('layouts.admin')

@section('title', 'Chi tiết sản phẩm')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <h2 class="mb-0">Chi tiết sản phẩm</h2>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="{{ route('admin.products.index', ['page' => session('admin.products.page', 1)]) }}">Quay lại</a>
        <a class="btn btn-primary" href="{{ route('admin.products.edit', $product) }}">Sửa sản phẩm</a>
    </div>
</div>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Ảnh sản phẩm</h6>
                @if($product->image)
                    <img src="/images/products/{{ basename($product->image) }}" alt="{{ $product->name }}" class="img-fluid rounded" style="max-height: 280px; object-fit: contain;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <span class="text-muted d-none">Không tải được ảnh</span>
                @else
                    <div class="bg-light rounded d-flex align-items-center justify-content-center py-5" style="min-height: 200px;">
                        <span class="text-muted">Chưa có ảnh</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header font-weight-bold">Thông tin chung</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Tên:</dt>
                    <dd class="col-sm-9">{{ $product->name }}</dd>

                    <dt class="col-sm-3">Danh mục:</dt>
                    <dd class="col-sm-9">{{ $product->category ? $product->category->full_path ?? $product->category->name : '—' }}</dd>

                    <dt class="col-sm-3">Thương hiệu:</dt>
                    <dd class="col-sm-9">{{ $product->brand->name ?? '—' }}</dd>

                    <dt class="col-sm-3">Mô tả:</dt>
                    <dd class="col-sm-9"><div class="text-secondary" style="white-space: pre-line;">{{ $product->description ?: '—' }}</div></dd>

                    <dt class="col-sm-3">Giá cũ:</dt>
                    <dd class="col-sm-9">
                        @if($product->old_price !== null)
                            <span style="text-decoration: line-through;" class="text-muted">{{ number_format($product->old_price, 0, ',', '.') }}₫</span>
                        @else
                            —
                        @endif
                    </dd>

                    <dt class="col-sm-3">Giá bán:</dt>
                    <dd class="col-sm-9"><strong class="text-danger" style="font-size: 1.1rem;">{{ number_format($product->price, 0, ',', '.') }}₫</strong></dd>

                    <dt class="col-sm-3">Tổng tồn kho:</dt>
                    <dd class="col-sm-9">{{ $product->quantity ?? 0 }}</dd>

                    <dt class="col-sm-3">Trạng thái:</dt>
                    <dd class="col-sm-9">
                        @if($product->is_active)
                            <span class="badge badge-success">Đang bán</span>
                        @else
                            <span class="badge badge-secondary">Ẩn</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>

@if($product->variants->isNotEmpty())
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="font-weight-bold">Biến thể ({{ $product->variants->count() }})</span>
        <a href="{{ route('admin.products.edit', $product) }}#variants" class="btn btn-sm btn-outline-primary">Sửa biến thể</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Thuộc tính</th>
                        <th class="text-right">Giá (₫)</th>
                        <th class="text-center">Tồn kho</th>
                        <th>Ảnh</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($product->variants as $v)
                    <tr>
                        <td>{{ $v->display_name }}</td>
                        <td class="text-right">{{ number_format($v->price, 0, ',', '.') }}₫</td>
                        <td class="text-center">{{ $v->stock }}</td>
                        <td>
                            @php $img = $v->images->first(); @endphp
                            @if($img)
                                <img src="/images/products/{{ basename($img->image) }}" alt="" class="img-thumbnail" style="width: 48px; height: 48px; object-fit: cover;">
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@else
<div class="card">
    <div class="card-body">
        <p class="text-muted mb-0">Sản phẩm không có biến thể (bán đơn).</p>
        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-primary mt-2">Sửa sản phẩm</a>
    </div>
</div>
@endif
@endsection
