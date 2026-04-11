@extends('layouts.admin')

@push('styles')
<style>
    .flash-sale-form-footer {
        gap: 0.75rem;
    }
    .flash-sale-form-footer .btn {
        border-radius: 0.5rem;
    }
</style>
@endpush

@section('title', 'Tạo Flash Sale')

@php
    $normDt = function (?string $v): string {
        if ($v === null || $v === '') {
            return '';
        }

        return str_replace('T', ' ', $v);
    };
    $fpStart = $normDt(old('start_time'));
    $fpEnd = $normDt(old('end_time'));
@endphp

@section('content')
<div class="page-header">
    <h2>Tạo chương trình Flash Sale</h2>
    <a href="{{ route('admin.flash-sales.index') }}" class="btn btn-outline-secondary">← Danh sách</a>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.flash-sales.store') }}" method="POST">
    @csrf
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="form-group">
                <label for="name">Tên chương trình <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="start_time">Thời gian bắt đầu <span class="text-danger">*</span></label>
                        <input type="text" name="start_time" id="start_time" class="form-control" value="{{ $fpStart }}" placeholder="dd/mm/yyyy hh:mm" autocomplete="off" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="end_time">Thời gian kết thúc <span class="text-danger">*</span></label>
                        <input type="text" name="end_time" id="end_time" class="form-control" value="{{ $fpEnd }}" placeholder="dd/mm/yyyy hh:mm" autocomplete="off" required>
                    </div>
                </div>
            </div>
            <p class="text-muted small mb-0">Giờ hiển thị theo định dạng <strong>24 giờ</strong>. Trạng thái chương trình được hệ thống gán tự động theo thời gian bắt đầu / kết thúc.</p>
        </div>
        <div class="card-footer flash-sale-form-footer d-flex flex-wrap align-items-center justify-content-center">
            <button type="submit" class="btn btn-danger">Tạo chương trình</button>
            <a href="{{ route('admin.flash-sales.index') }}" class="btn btn-secondary">Hủy</a>
        </div>
    </div>
</form>

@include('admin.flash_sales._flatpickr_24h', ['fpMode' => 'create'])
@endsection
