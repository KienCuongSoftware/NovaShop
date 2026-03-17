@extends('layouts.admin')

@section('title', 'Thêm thuộc tính')

@section('content')
<div class="page-header">
    <h2>Thêm thuộc tính</h2>
    <div class="admin-toolbar">
        <a class="btn btn-outline-secondary" href="{{ route('admin.attributes.index') }}">← Danh sách</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.attributes.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="name">Tên thuộc tính <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" placeholder="vd: Màu sắc, Size, Loại" required maxlength="255">
                @error('name')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn btn-success">Lưu</button>
            <a href="{{ route('admin.attributes.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </form>
    </div>
</div>
@endsection
