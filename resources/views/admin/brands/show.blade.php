@extends('layouts.admin')

@section('title', 'Chi tiết thương hiệu')

@section('content')
<div class="page-header">
    <h2>Chi tiết thương hiệu</h2>
    <a class="btn btn-primary" href="{{ route('admin.brands.index', ['page' => session('admin.brands.page', 1)]) }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Tên:</dt>
            <dd class="col-sm-9">{{ $brand->name }}</dd>
            <dt class="col-sm-3">Slug:</dt>
            <dd class="col-sm-9">{{ $brand->slug }}</dd>
            @if($brand->logo)
            <dt class="col-sm-3">Logo:</dt>
            <dd class="col-sm-9">
                <img src="/images/brands/{{ basename($brand->logo) }}" alt="{{ $brand->name }}" class="img-thumbnail" style="max-height: 120px; object-fit: contain;">
            </dd>
            @endif
            <dt class="col-sm-3">Số sản phẩm:</dt>
            <dd class="col-sm-9">{{ $brand->products_count ?? 0 }}</dd>
        </dl>
        <hr>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="{{ route('admin.brands.edit', $brand) }}">Sửa</a>
            <form action="{{ route('admin.brands.destroy', $brand) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa thương hiệu \'{{ addslashes($brand->name) }}\'?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Xóa</button>
            </form>
        </div>
    </div>
</div>
@endsection
