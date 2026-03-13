@extends('layouts.admin')

@section('title', 'Sửa danh mục')

@section('content')
<div class="page-header">
    <h2>Sửa danh mục</h2>
    <a class="btn btn-primary" href="{{ route('admin.categories.index', ['page' => session('admin.categories.page', 1)]) }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="name"><strong>Tên:</strong></label>
                <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" class="form-control" placeholder="Nhập tên danh mục" required>
            </div>
            <div class="form-group">
                <label for="image"><strong>Ảnh danh mục:</strong></label>
                <div class="d-flex flex-wrap align-items-start mb-2" style="gap: 1rem;">
                    @if($category->image)
                        <div>
                            <img src="/images/categories/{{ basename($category->image) }}" alt="{{ $category->name }}" class="img-thumbnail" style="width: 200px; height: 200px; object-fit: cover;">
                            <span class="text-muted small d-block">Ảnh hiện tại</span>
                        </div>
                    @endif
                    <div id="preview-image" class="image-preview-wrap" style="display: none;">
                        <img src="" alt="Preview" class="img-thumbnail" style="width: 200px; height: 200px; object-fit: cover;">
                        <span class="text-muted small d-block">Ảnh mới</span>
                    </div>
                </div>
                <input type="file" name="image" id="image" class="form-control-file" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                <small class="form-text text-muted">JPEG, PNG, GIF, WebP; tối đa 2MB. Chọn file mới để thay ảnh.</small>
            </div>
            <hr>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Cập nhật</button>
            </div>
        </form>
    </div>
</div>
@endsection
