@extends('layouts.admin')

@section('title', 'Thêm người dùng')

@section('content')
<div class="page-header">
    <h2>Thêm người dùng</h2>
    <a class="btn btn-primary" href="{{ route('admin.users.index', ['page' => session('admin.users.page', 1)]) }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="avatar"><strong>Ảnh đại diện:</strong></label>
                <input type="file" name="avatar" id="avatar" class="form-control-file" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                <div id="preview-avatar" class="image-preview-wrap mt-2" style="display: none;">
                    <img src="" alt="Preview" class="img-thumbnail rounded-circle" style="width: 200px; height: 200px; object-fit: cover;">
                    <span class="text-muted small d-block">Ảnh mới</span>
                </div>
                <small class="form-text text-muted">JPEG, PNG, GIF, WebP; tối đa 2MB. Để trống nếu không cần.</small>
                @error('avatar')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="name"><strong>Tên:</strong></label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Nhập tên" value="{{ old('name') }}" required>
                @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="email"><strong>Email:</strong></label>
                <input type="email" name="email" id="email" class="form-control" placeholder="Nhập email" value="{{ old('email') }}" required>
                @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="birthday"><strong>Ngày sinh:</strong></label>
                <input type="date" name="birthday" id="birthday" class="form-control" value="{{ old('birthday') }}">
                <small class="form-text text-muted">Tuỳ chọn — cho coupon sinh nhật.</small>
                @error('birthday')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="password"><strong>Mật khẩu:</strong></label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Nhập mật khẩu" required>
                @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="password_confirmation"><strong>Xác nhận mật khẩu:</strong></label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Nhập lại mật khẩu">
            </div>
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="hidden" name="is_admin" value="0">
                    <input type="checkbox" class="custom-control-input" name="is_admin" id="is_admin" value="1" {{ old('is_admin') ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_admin">Là quản trị viên</label>
                </div>
            </div>
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="hidden" name="is_staff" value="0">
                    <input type="checkbox" class="custom-control-input" name="is_staff" id="is_staff" value="1" {{ old('is_staff') ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_staff">Là nhân viên (đơn hàng, đánh giá, kho)</label>
                </div>
                <small class="form-text text-muted">Đăng nhập tại <code>/staff/login</code>.</small>
            </div>
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="hidden" name="is_vip" value="0">
                    <input type="checkbox" class="custom-control-input" name="is_vip" id="is_vip" value="1" {{ old('is_vip') ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_vip">Khách VIP</label>
                </div>
                <small class="form-text text-muted">Dùng cho coupon segment VIP.</small>
            </div>
            <hr>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>
@endsection
