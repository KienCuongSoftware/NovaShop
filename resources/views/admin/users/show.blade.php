@extends('layouts.admin')

@section('title', 'Chi tiết người dùng')

@section('content')
<div class="page-header">
    <h2>Chi tiết người dùng</h2>
    <div class="admin-toolbar">
        @if($user->id !== auth()->id())
            <form method="POST" action="{{ route('admin.users.toggle-block', $user) }}" class="d-inline" onsubmit="return bsConfirmSubmit(this, @json(($user->is_blocked ?? false)
                ? ('Bỏ chặn tài khoản '.$user->email.'?'.chr(10).chr(10).'Người dùng có thể đăng nhập lại.')
                : ('Xác nhận CHẶN tài khoản '.$user->email.'?'.chr(10).chr(10).'Sau khi chặn, người dùng không thể đăng nhập.'.chr(10).'Hệ thống sẽ gửi email thông báo tới địa chỉ này (cần cấu hình SMTP trong .env nếu muốn gửi thật tới Gmail).')));">
                @csrf
                <button type="submit" class="btn {{ ($user->is_blocked ?? false) ? 'btn-success' : 'btn-danger' }}">
                    {{ ($user->is_blocked ?? false) ? 'Bỏ chặn' : 'Chặn tài khoản' }}
                </button>
            </form>
        @endif
        <a class="btn btn-primary" href="{{ route('admin.users.index', ['page' => session('admin.users.page', 1)]) }}">Quay lại</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Ảnh đại diện:</dt>
            <dd class="col-sm-9">
                @if($user->avatar)
                    <img src="/images/avatars/{{ basename($user->avatar) }}" alt="{{ $user->name }}" class="rounded-circle img-thumbnail" style="width: 120px; height: 120px; object-fit: cover;">
                @else
                    <x-user-avatar :user="$user" :size="120" class="rounded-circle img-thumbnail" />
                @endif
            </dd>
            <dt class="col-sm-3">Tên:</dt>
            <dd class="col-sm-9">{{ $user->name }}</dd>
            <dt class="col-sm-3">Email:</dt>
            <dd class="col-sm-9">{{ $user->email }}</dd>
            <dt class="col-sm-3">Quản trị viên:</dt>
            <dd class="col-sm-9">{{ $user->is_admin ? 'Có' : 'Không' }}</dd>
            <dt class="col-sm-3">Trạng thái tài khoản:</dt>
            <dd class="col-sm-9">
                @if($user->is_blocked ?? false)
                    <span class="badge badge-dark">Đã chặn — không thể đăng nhập</span>
                @else
                    <span class="badge badge-success">Đang hoạt động</span>
                @endif
            </dd>
        </dl>
    </div>
</div>
@endsection
