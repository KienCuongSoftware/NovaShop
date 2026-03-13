@extends('layouts.auth')

@section('title', 'Đăng nhập')

@section('content')
<h2 class="auth-title">Đăng nhập</h2>

<form method="POST" action="{{ route('login') }}">
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
    <a href="{{ route('register') }}" class="btn btn-link">Chưa có tài khoản? Đăng ký</a>
</form>
@endsection
