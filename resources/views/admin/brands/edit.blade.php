@extends('layouts.admin')

@section('title', 'Sửa thương hiệu')

@section('content')
<div class="page-header">
    <h2>Sửa thương hiệu</h2>
    <a class="btn btn-primary" href="{{ route('admin.brands.index', ['page' => session('admin.brands.page', 1)]) }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.brands.update', $brand) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="name"><strong>Tên thương hiệu:</strong></label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="Nhập tên thương hiệu" value="{{ old('name', $brand->name) }}" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><strong>Logo:</strong></label>
                        <div class="border rounded p-2 bg-light text-center">
                            @if($brand->logo)
                                <img id="current-img" src="/images/brands/{{ basename($brand->logo) }}" alt="{{ $brand->name }}" class="img-fluid" style="max-height: 120px; object-fit: contain;">
                                <p class="text-muted small mt-1 mb-0">Ảnh hiện tại</p>
                            @else
                                <div id="current-img" class="py-4 text-muted">Chưa có logo</div>
                            @endif
                            <img id="preview-img" src="" alt="" class="img-fluid mt-2" style="max-height: 120px; object-fit: contain; display: none;">
                        </div>
                        <input type="file" name="logo" id="logo" class="form-control-file mt-2" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                        <small class="form-text text-muted">JPEG, PNG, GIF, WebP; tối đa 2MB</small>
                    </div>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Cập nhật</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('logo').addEventListener('change', function() {
    var preview = document.getElementById('preview-img');
    var current = document.getElementById('current-img');
    if (this.files && this.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (current) current.style.display = 'none';
        };
        reader.readAsDataURL(this.files[0]);
    }
});
</script>
@endsection
