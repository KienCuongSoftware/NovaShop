@extends('layouts.auth')

@section('title', 'Đăng ký')

@section('content')
<h2 class="auth-title">Đăng ký tài khoản</h2>

<form method="POST" action="{{ route('register') }}">
    @csrf
    <div class="form-group">
        <label for="name">Tên</label>
        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required autofocus>
    </div>
    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required>
    </div>
    <div class="form-group">
        <label for="password">Mật khẩu</label>
        <input type="password" name="password" id="password" class="form-control" required>
    </div>
    <div class="form-group">
        <label for="password_confirmation">Xác nhận mật khẩu</label>
        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-auth-primary">Đăng ký</button>
    <a href="{{ route('login') }}" class="btn btn-link">Đã có tài khoản? Đăng nhập</a>
</form>
@endsection
