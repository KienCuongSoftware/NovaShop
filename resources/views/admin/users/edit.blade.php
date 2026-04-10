@extends('layouts.admin')

@section('title', 'Sửa người dùng')

@section('content')
<div class="page-header">
    <h2>Sửa người dùng</h2>
    <a class="btn btn-primary" href="{{ route('admin.users.index', ['page' => session('admin.users.page', 1)]) }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.users.update', $user) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="avatar"><strong>Ảnh đại diện:</strong></label>
                <div class="d-flex flex-wrap align-items-start mb-2" style="gap: 1rem;">
                    @if($user->avatar)
                        <div>
                            <img src="/images/avatars/{{ basename($user->avatar) }}" alt="{{ $user->name }}" class="rounded-circle img-thumbnail" style="width: 200px; height: 200px; object-fit: cover;">
                            <span class="text-muted small d-block">Ảnh hiện tại</span>
                        </div>
                    @else
                        <div>
                            <x-user-avatar :user="$user" :size="200" class="rounded-circle img-thumbnail" />
                            <span class="text-muted small d-block">Ảnh hiện tại (chữ cái)</span>
                        </div>
                    @endif
                    <div id="preview-avatar" class="image-preview-wrap" style="display: none;">
                        <img src="" alt="Preview" class="img-thumbnail rounded-circle" style="width: 200px; height: 200px; object-fit: cover;">
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
ph                <label for="birthday"><strong>Ngày sinh:</strong></label>
                <input type="date" name="birthday" id="birthday" class="form-control" value="{{ old('birthday', $user->birthday?->format('Y-m-d')) }}">
                <small class="form-text text-muted">Dùng cho mã giảm giá theo sinh nhật (tuỳ chọn).</small>
                @error('birthday')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
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
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="hidden" name="is_admin" value="0">
                    <input type="checkbox" class="custom-control-input" name="is_admin" id="is_admin" value="1" {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_admin">Là quản trị viên</label>
                </div>
            </div>
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="hidden" name="is_vip" value="0">
                    <input type="checkbox" class="custom-control-input" name="is_vip" id="is_vip" value="1" {{ old('is_vip', $user->is_vip ?? false) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_vip">Khách VIP</label>
                </div>
                <small class="form-text text-muted">Dùng cho coupon segment VIP.</small>
            </div>
            <hr>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Cập nhật</button>
            </div>
        </form>
    </div>
</div>
@endsection
