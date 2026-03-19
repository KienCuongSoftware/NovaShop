@extends('layouts.user')

@section('title', 'Sửa địa chỉ - NovaShop')

@section('content')
<div class="page-header mb-4">
    <h2>Sửa địa chỉ</h2>
    <a href="{{ route('addresses.index') }}" class="btn btn-outline-secondary">← Sổ địa chỉ</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('addresses.update', $address) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label>Nhãn (tùy chọn)</label>
                <input type="text" name="label" class="form-control" value="{{ old('label', $address->label) }}" placeholder="VD: Nhà riêng, Công ty" maxlength="50">
                @error('label')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Họ tên <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $address->full_name) }}" required>
                        @error('full_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Số điện thoại <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $address->phone) }}" required>
                        @error('phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Địa chỉ <span class="text-danger">*</span></label>
                <div class="position-relative">
                    <input type="text" id="address" name="address" class="form-control" value="{{ old('address', $address->address_line) }}" required placeholder="Tìm kiếm hoặc chọn trên bản đồ" autocomplete="off">
                    <div id="address-suggest-dropdown" class="d-none bg-white border rounded shadow-sm position-absolute w-100" style="z-index: 1000; max-height: 220px; overflow-y: auto; top: 100%; left: 0; right: 0;"></div>
                </div>
                @error('address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <input type="hidden" name="lat" id="lat" value="{{ old('lat', $address->lat) }}">
            <input type="hidden" name="lng" id="lng" value="{{ old('lng', $address->lng) }}">
            @error('lat')<div class="text-danger small">{{ $message }}</div>@enderror
            @error('lng')<div class="text-danger small">{{ $message }}</div>@enderror

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" name="is_default" value="1" id="is_default" class="custom-control-input" {{ old('is_default', $address->is_default) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_default">Đặt làm địa chỉ mặc định</label>
                </div>
            </div>

            @include('partials.leaflet-address-picker', [
                'mapId' => 'map',
                'showGeolocate' => true,
                'initialLatLng' => [$address->lat ?? 10.762622, $address->lng ?? 106.660172]
            ])

            <hr>
            <button type="submit" class="btn btn-danger">Cập nhật</button>
            <a href="{{ route('addresses.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </form>
    </div>
</div>

<style>
#address-suggest-dropdown .address-suggest-item:hover { background: #f8f9fa; }
</style>
@endsection
