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
                <label for="image"><strong>Ảnh danh mục:</strong></label>
                <input type="file" name="image" id="image" class="form-control-file" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
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
