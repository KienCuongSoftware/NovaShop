@extends('layouts.admin')

@section('title', 'Sửa người dùng')

@section('content')
<div class="page-header">
    <h2>Sửa người dùng</h2>
    <a class="btn btn-primary" href="{{ route('admin.users.index', ['page' => session('admin.users.page', 1)]) }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')
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
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="hidden" name="is_admin" value="0">
                    <input type="checkbox" class="custom-control-input" name="is_admin" id="is_admin" value="1" {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_admin">Là quản trị viên</label>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Cập nhật</button>
            </div>
        </form>
    </div>
</div>
@endsection
