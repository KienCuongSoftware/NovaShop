@extends('layouts.admin')

@section('title', 'Sửa danh mục')

@section('content')
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb bg-transparent p-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.categories.index') }}">Danh mục</a></li>
        <li class="breadcrumb-item active" aria-current="page">Sửa: {{ $category->name }}</li>
    </ol>
</nav>

<div class="page-header">
    <h2>Sửa danh mục</h2>
    <a class="btn btn-outline-secondary" href="{{ route('admin.categories.index', ['page' => session('admin.categories.page', 1)]) }}">← Quay lại</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="name">Tên danh mục <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" class="form-control form-control-lg" placeholder="Nhập tên danh mục" required>
                    </div>
                    <div class="form-group">
                        <label for="parent_id">Danh mục cha</label>
                        <select name="parent_id" id="parent_id" class="form-control">
                            <option value="">— Không (danh mục gốc) —</option>
                            @foreach($parentCategories ?? [] as $root)
                                @if(!in_array($root->id, $excludeIds ?? []))
                                <option value="{{ $root->id }}" {{ old('parent_id', $category->parent_id) == $root->id ? 'selected' : '' }}>{{ $root->name }}</option>
                                @endif
                                @foreach($root->children ?? [] as $child)
                                    @if(!in_array($child->id, $excludeIds ?? []))
                                    <option value="{{ $child->id }}" {{ old('parent_id', $category->parent_id) == $child->id ? 'selected' : '' }}>　└ {{ $child->name }}</option>
                                    @endif
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>
                @if(!$category->parent_id)
                <div class="col-md-4" id="category-image-col">
                    <div class="form-group">
                        <label>Ảnh danh mục</label>
                        <div class="border rounded p-2 bg-light text-center">
                            @if($category->image)
                                <img id="current-img" src="/images/categories/{{ basename($category->image) }}" alt="{{ $category->name }}" class="img-fluid rounded" style="max-height: 160px; object-fit: cover;">
                                <p class="text-muted small mt-1 mb-0">Ảnh hiện tại</p>
                            @else
                                <div id="current-img" class="py-4 text-muted">Chưa có ảnh</div>
                            @endif
                            <img id="preview-img" src="" alt="" class="img-fluid rounded mt-2" style="max-height: 120px; object-fit: cover; display: none;">
                        </div>
                        <input type="file" name="image" id="image" class="form-control-file mt-2" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" onchange="previewImage(this)">
                        <small class="form-text text-muted">Chỉ danh mục gốc. JPEG, PNG, GIF, WebP; tối đa 2MB</small>
                    </div>
                </div>
                @endif
            </div>
            <hr>
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('admin.categories.index') }}" class="btn btn-link text-muted">Hủy</a>
                <button type="submit" class="btn btn-primary px-4">Cập nhật</button>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(input) {
    var preview = document.getElementById('preview-img');
    var current = document.getElementById('current-img');
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (current) current.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection
