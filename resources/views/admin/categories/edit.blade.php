@extends('layouts.admin')

@section('title', 'Sửa danh mục')

@section('content')
<div class="page-header">
    <h2>Sửa danh mục</h2>
    <a class="btn btn-primary" href="{{ route('admin.categories.index') }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.categories.update', $category->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="name"><strong>Tên:</strong></label>
                <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" class="form-control" placeholder="Nhập tên danh mục" required>
            </div>
            <div class="form-group">
                <label for="image"><strong>Ảnh danh mục:</strong></label>
                @if($category->image)
                    <div class="mb-2">
                        <img src="/images/categories/{{ basename($category->image) }}" alt="{{ $category->name }}" class="img-thumbnail" style="max-height: 80px;">
                        <span class="text-muted small">Ảnh hiện tại</span>
                    </div>
                @endif
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
