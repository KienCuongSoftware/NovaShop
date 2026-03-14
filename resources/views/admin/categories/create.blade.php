@extends('layouts.admin')

@section('title', 'Tạo danh mục mới')

@section('content')
<div class="page-header">
    <h2>Tạo danh mục mới</h2>
    <a class="btn btn-primary" href="{{ route('admin.categories.index', ['page' => session('admin.categories.page', 1)]) }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="name"><strong>Tên:</strong></label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Nhập tên danh mục" value="{{ old('name') }}" required>
            </div>
            <div class="form-group">
                <label for="parent_id"><strong>Danh mục cha:</strong></label>
                <select name="parent_id" id="parent_id" class="form-control">
                    <option value="">— Không (danh mục gốc) —</option>
                    @foreach($parentCategories ?? [] as $root)
                        <option value="{{ $root->id }}" {{ old('parent_id') == $root->id ? 'selected' : '' }}>{{ $root->name }}</option>
                        @foreach($root->children ?? [] as $child)
                            <option value="{{ $child->id }}" {{ old('parent_id') == $child->id ? 'selected' : '' }}>　└ {{ $child->name }}</option>
                        @endforeach
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="image"><strong>Ảnh danh mục:</strong></label>
                <input type="file" name="image" id="image" class="form-control-file" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                <div id="preview-image" class="image-preview-wrap mt-2" style="display: none;">
                    <img src="" alt="Preview" class="img-thumbnail" style="width: 200px; height: 200px; object-fit: cover;">
                    <span class="text-muted small d-block">Ảnh mới</span>
                </div>
                <small class="form-text text-muted">JPEG, PNG, GIF, WebP; tối đa 2MB. Để trống nếu không cần.</small>
            </div>
            <hr>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>
@endsection
