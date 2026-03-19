@extends('layouts.user')

@section('title', 'Thêm địa chỉ - NovaShop')

@section('content')
<div class="page-header mb-4">
    <h2>Thêm địa chỉ</h2>
    <a href="{{ route('addresses.index') }}" class="btn btn-outline-secondary">← Sổ địa chỉ</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('addresses.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Nhãn (tùy chọn)</label>
                <input type="text" name="label" class="form-control" value="{{ old('label') }}" placeholder="VD: Nhà riêng, Công ty" maxlength="50">
                @error('label')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Họ tên <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" value="{{ old('full_name', auth()->user()->name ?? '') }}" required>
                        @error('full_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Số điện thoại <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required placeholder="0912345678">
                        @error('phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Địa chỉ <span class="text-danger">*</span></label>
                <div class="position-relative">
                    <input type="text" id="address" name="address" class="form-control" value="{{ old('address') }}" required placeholder="Tìm kiếm hoặc chọn trên bản đồ" autocomplete="off">
                    <div id="address-suggest-dropdown" class="d-none bg-white border rounded shadow-sm position-absolute w-100" style="z-index: 1000; max-height: 220px; overflow-y: auto; top: 100%; left: 0; right: 0;"></div>
                </div>
                <small class="form-text text-muted">Gõ địa chỉ để gợi ý, hoặc nhấn vào bản đồ / kéo marker.</small>
                @error('address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <input type="hidden" name="lat" id="lat" value="{{ old('lat') }}">
            <input type="hidden" name="lng" id="lng" value="{{ old('lng') }}">
            @error('lat')<div class="text-danger small">{{ $message }}</div>@enderror
            @error('lng')<div class="text-danger small">{{ $message }}</div>@enderror

            @include('partials.leaflet-address-picker', ['mapId' => 'map', 'showGeolocate' => true])

            <hr>
            <button type="submit" class="btn btn-danger">Lưu địa chỉ</button>
            <a href="{{ route('addresses.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </form>
    </div>
</div>

<style>
#address-suggest-dropdown .address-suggest-item:hover { background: #f8f9fa; }
</style>
@endsection
