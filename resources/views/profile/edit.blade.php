@extends('layouts.user')

@section('title', 'Quản lý tài khoản')

@section('content')
<div class="page-header">
    <h2>Quản lý tài khoản</h2>
    <a class="btn btn-primary" href="{{ url('/') }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="avatar"><strong>Ảnh đại diện:</strong></label>
                @if($user->avatar)
                    <div class="mb-2">
                        <img src="/images/avatars/{{ basename($user->avatar) }}" alt="{{ $user->name }}" class="rounded-circle img-thumbnail" style="width: 80px; height: 80px; object-fit: cover;">
                        <span class="text-muted small d-block">Ảnh hiện tại</span>
                    </div>
                @endif
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
                <button type="submit" class="btn btn-primary">Cập nhật</button>
            </div>
        </form>
    </div>
</div>
@endsection
