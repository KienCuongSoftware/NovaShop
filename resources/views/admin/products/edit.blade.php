@extends('layouts.admin')

@section('title', 'Sửa sản phẩm')

@section('content')
<div class="page-header">
    <h2>Sửa sản phẩm</h2>
    <a class="btn btn-primary" href="{{ route('admin.products.index', ['page' => session('admin.products.page', 1)]) }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="category_id"><strong>Danh mục:</strong></label>
                <select name="category_id" id="category_id" class="form-control" required>
                    <option value="">-- Chọn danh mục --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (old('category_id', $product->category_id) == $cat->id) ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="name"><strong>Tên sản phẩm:</strong></label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Nhập tên sản phẩm" value="{{ old('name', $product->name) }}" required>
            </div>
            <div class="form-group">
                <label for="description"><strong>Mô tả:</strong></label>
                <textarea name="description" id="description" class="form-control" rows="3" placeholder="Mô tả sản phẩm">{{ old('description', $product->description) }}</textarea>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="price"><strong>Giá mới (₫):</strong></label>
                        <input type="number" name="price" id="price" class="form-control" placeholder="0" value="{{ old('price', $product->price) }}" min="0" step="1000" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="old_price"><strong>Giá cũ (₫):</strong></label>
                        <input type="number" name="old_price" id="old_price" class="form-control" placeholder="Không có" value="{{ old('old_price', $product->old_price) }}" min="0" step="1000">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="quantity"><strong>Số lượng:</strong></label>
                        <input type="number" name="quantity" id="quantity" class="form-control" placeholder="0" value="{{ old('quantity', $product->quantity) }}" min="0" required>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="image"><strong>Hình ảnh:</strong></label>
                <div class="d-flex flex-wrap align-items-start mb-2" style="gap: 1rem;">
                    @if($product->image)
                        <div>
                            <img src="/images/products/{{ basename($product->image) }}" alt="{{ $product->name }}" class="img-thumbnail" style="width: 200px; height: 200px; object-fit: cover;">
                            <span class="text-muted small d-block">Ảnh hiện tại</span>
                        </div>
                    @endif
                    <div id="preview-image" class="image-preview-wrap" style="display: none;">
                        <img src="" alt="Preview" class="img-thumbnail" style="width: 200px; height: 200px; object-fit: cover;">
                        <span class="text-muted small d-block">Ảnh mới</span>
                    </div>
                </div>
                <input type="file" name="image" id="image" class="form-control-file" accept="image/*">
            </div>
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" class="custom-control-input" name="is_active" id="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_active">Đang bán</label>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Cập nhật</button>
            </div>
        </form>
    </div>
</div>
@endsection
