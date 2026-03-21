@extends('layouts.admin')

@section('title', 'Thông tin tài khoản')

@section('content')
<div class="page-header">
    <h2>Thông tin tài khoản</h2>
    <a class="btn btn-outline-secondary" href="{{ route('admin.dashboard') }}">Quay lại</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="avatar"><strong>Ảnh đại diện:</strong></label>
                <div class="d-flex flex-wrap align-items-start mb-2" style="gap: 1rem;">
                    @if($user->avatar)
                        <div>
                            <img src="/images/avatars/{{ basename($user->avatar) }}" alt="{{ $user->name }}" class="rounded-circle img-thumbnail" style="width: 120px; height: 120px; object-fit: cover;">
                            <span class="text-muted small d-block">Ảnh hiện tại</span>
                        </div>
                    @endif
                    <div id="preview-avatar" class="image-preview-wrap" style="display: none;">
                        <img src="" alt="Preview" class="img-thumbnail rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                        <span class="text-muted small d-block">Ảnh mới</span>
                    </div>
                </div>
                <input type="file" name="avatar" id="avatar" class="form-control-file" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                <small class="form-text text-muted">JPEG, PNG, GIF, WebP; tối đa 2MB. Chọn file mới để thay ảnh.</small>
                @error('avatar')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="name"><strong>Tên:</strong></label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Nhập tên" value="{{ old('name', $user->name) }}" required>
                @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="email"><strong>Email:</strong></label>
                <input type="email" name="email" id="email" class="form-control" placeholder="Nhập email" value="{{ old('email', $user->email) }}" required>
                @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="password"><strong>Mật khẩu mới:</strong></label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Để trống nếu không đổi">
                <small class="form-text text-muted">Chỉ nhập khi muốn thay đổi mật khẩu.</small>
                @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="password_confirmation"><strong>Xác nhận mật khẩu mới:</strong></label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Nhập lại mật khẩu mới">
            </div>
            <hr>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-danger">Cập nhật</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('avatar').addEventListener('change', function(e) {
    var file = e.target.files[0];
    var wrap = document.getElementById('preview-avatar');
    if (!file || !file.type.match('image.*')) {
        wrap.style.display = 'none';
        return;
    }
    var reader = new FileReader();
    reader.onload = function(e) {
        wrap.querySelector('img').src = e.target.result;
        wrap.style.display = 'block';
    };
    reader.readAsDataURL(file);
});
</script>
@endpush
@endsection
