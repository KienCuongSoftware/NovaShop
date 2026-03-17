@extends('layouts.admin')

@section('title', 'Sửa thuộc tính')

@section('content')
<div class="page-header">
    <h2>Sửa thuộc tính: {{ $attribute->name }}</h2>
    <div class="admin-toolbar">
        <a class="btn btn-outline-secondary" href="{{ route('admin.attributes.index') }}">← Danh sách</a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('admin.attributes.update', $attribute) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="name">Tên thuộc tính <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $attribute->name) }}" required maxlength="255">
                @error('name')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary">Cập nhật tên</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Giá trị thuộc tính</h5>
        <form action="{{ route('admin.attributes.values.store', $attribute) }}" method="POST" class="form-inline">
            @csrf
            <input type="text" name="value" class="form-control form-control-sm mr-2" placeholder="Thêm giá trị mới" style="width: 200px;" required maxlength="255">
            <button type="submit" class="btn btn-success btn-sm">Thêm</button>
        </form>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>ID</th>
                    <th>Giá trị</th>
                    <th style="width: 100px;" class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attribute->attributeValues as $av)
                <tr>
                    <td class="align-middle">{{ $av->id }}</td>
                    <td class="align-middle">{{ $av->value }}</td>
                    <td class="align-middle text-right">
                        <form action="{{ route('admin.attributes.values.destroy', [$attribute, $av]) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa giá trị này?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">Xóa</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center text-muted py-4">Chưa có giá trị. Thêm giá trị ở ô trên (vd: Đen, Trắng, Nhỏ, Lớn).</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
