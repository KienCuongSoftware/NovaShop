@extends('layouts.admin')

@section('title', 'Trang quản trị')

@section('content')
<div class="page-header">
    <h2>Trang quản trị</h2>
</div>

<div class="card">
    <div class="card-body">
        <p class="mb-0">Chào mừng <strong>{{ auth()->user()->name }}</strong> đến trang quản trị.</p>
        <p class="text-muted small mt-2 mb-0">Bạn đang đăng nhập với quyền admin.</p>
    </div>
</div>
@endsection
