@extends('layouts.admin')

@section('title', 'Chi tiết người dùng')

@section('content')
<div class="page-header">
    <h2>Chi tiết người dùng</h2>
    <a class="btn btn-primary" href="{{ route('admin.users.index', ['page' => session('admin.users.page', 1)]) }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            @if($user->avatar)
            <dt class="col-sm-3">Ảnh đại diện:</dt>
            <dd class="col-sm-9">
                <img src="/images/avatars/{{ basename($user->avatar) }}" alt="{{ $user->name }}" class="rounded-circle img-thumbnail" style="width: 120px; height: 120px; object-fit: cover;">
            </dd>
            @endif
            <dt class="col-sm-3">Tên:</dt>
            <dd class="col-sm-9">{{ $user->name }}</dd>
            <dt class="col-sm-3">Email:</dt>
            <dd class="col-sm-9">{{ $user->email }}</dd>
            <dt class="col-sm-3">Quản trị viên:</dt>
            <dd class="col-sm-9">{{ $user->is_admin ? 'Có' : 'Không' }}</dd>
        </dl>
    </div>
</div>
@endsection
