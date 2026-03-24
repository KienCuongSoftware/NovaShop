@extends('layouts.user')

@section('title', 'Sổ địa chỉ - NovaShop')

@section('content')
<div class="page-header mb-4">
    <h2>Sổ địa chỉ</h2>
    <a href="{{ route('addresses.create') }}" class="btn btn-danger">Thêm địa chỉ</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">
    @forelse($addresses as $address)
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="card h-100 {{ $address->is_default ? 'border-danger' : '' }}">
            <div class="card-body">
                @if($address->label)
                    <span class="badge badge-secondary mb-2">{{ $address->label }}</span>
                    @if($address->is_default)
                        <span class="badge badge-danger mb-2">Mặc định</span>
                    @endif
                @elseif($address->is_default)
                    <span class="badge badge-danger mb-2">Mặc định</span>
                @endif
                <p class="mb-1"><strong>{{ $address->full_name }}</strong> · {{ $address->phone }}</p>
                <p class="text-muted small mb-2">{{ $address->full_address }}</p>
                @if($address->hasCoordinates())
                    <small class="text-muted">Tọa độ: {{ number_format($address->lat, 5) }}, {{ number_format($address->lng, 5) }}</small>
                @endif
                <div class="mt-3 d-flex flex-wrap gap-2">
                    <a href="{{ route('addresses.edit', $address) }}" class="btn btn-outline-primary btn-sm">Sửa</a>
                    @if(!$address->is_default)
                        <form action="{{ route('addresses.set-default', $address) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-sm">Đặt mặc định</button>
                        </form>
                    @endif
                    <form action="{{ route('addresses.destroy', $address) }}" method="POST" class="d-inline" onsubmit="return bsConfirmSubmit(this, 'Xóa địa chỉ này?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">Xóa</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <p class="mb-3">Bạn chưa có địa chỉ nào.</p>
                <a href="{{ route('addresses.create') }}" class="btn btn-danger">Thêm địa chỉ</a>
            </div>
        </div>
    </div>
    @endforelse
</div>
@endsection
