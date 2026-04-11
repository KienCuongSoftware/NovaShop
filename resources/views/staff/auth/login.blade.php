@extends('layouts.auth')

@section('title', 'Đăng nhập nhân viên')

@section('content')
<h2 class="auth-title">Đăng nhập nhân viên</h2>
<p class="text-muted small mb-3">Khu vực xử lý đơn hàng, duyệt đánh giá và nhật ký kho.</p>

<form method="POST" action="{{ route('staff.login.submit') }}">
    @csrf
    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required autofocus>
    </div>
    <div class="form-group">
        <label for="password">Mật khẩu</label>
        <input type="password" name="password" id="password" class="form-control" required>
    </div>
    <div class="form-group">
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" name="remember" id="remember">
            <label class="custom-control-label" for="remember">Ghi nhớ đăng nhập</label>
        </div>
    </div>
    <button type="submit" class="btn btn-auth-primary">Đăng nhập</button>
    <a href="{{ route('login') }}" class="btn btn-link d-block mt-2">Về đăng nhập khách hàng</a>
    <a href="{{ route('admin.login') }}" class="btn btn-link d-block small">Đăng nhập quản trị</a>
</form>
@endsection
