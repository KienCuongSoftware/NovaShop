@extends('layouts.admin')

@section('title', 'Thêm thương hiệu')

@section('content')
<div class="page-header">
    <h2>Thêm thương hiệu</h2>
    <a class="btn btn-primary" href="{{ route('admin.brands.index', ['page' => session('admin.brands.page', 1)]) }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.brands.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="name"><strong>Tên thương hiệu:</strong></label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Nhập tên thương hiệu" value="{{ old('name') }}" required>
            </div>
            <div class="form-group">
                <label for="logo"><strong>Logo:</strong></label>
                <input type="file" name="logo" id="logo" class="form-control-file" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                <div id="preview-image" class="mt-2" style="display: none;">
                    <img src="" alt="Preview" class="img-thumbnail" style="width: 120px; height: 120px; object-fit: contain;">
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

<script>
document.getElementById('logo').addEventListener('change', function() {
    var preview = document.getElementById('preview-image');
    var img = preview.querySelector('img');
    if (this.files && this.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(this.files[0]);
    } else {
        preview.style.display = 'none';
    }
});
</script>
@endsection
