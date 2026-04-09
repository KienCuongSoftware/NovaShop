@extends('layouts.auth')

@section('title', 'Xác thực email')

@section('content')
<h2 class="auth-title">Xác thực email bằng OTP</h2>
<p class="text-muted">Nhập mã 6 số đã được gửi tới email của bạn. Mã có hiệu lực trong 10 phút.</p>

<form method="POST" action="{{ route('verification.otp.verify') }}">
    @csrf
    <div class="form-group">
        <label for="otp">Mã OTP</label>
        <input
            type="text"
            name="otp"
            id="otp"
            inputmode="numeric"
            pattern="[0-9]{6}"
            maxlength="6"
            class="form-control"
            value="{{ old('otp') }}"
            placeholder="Nhập 6 chữ số"
            required
            autofocus
        >
    </div>
    <button type="submit" class="btn btn-auth-primary">Xác thực</button>
</form>

<form method="POST" action="{{ route('verification.otp.resend') }}" class="mt-2">
    @csrf
    <button type="submit" class="btn btn-link p-0">Gửi lại mã OTP</button>
</form>
@endsection
