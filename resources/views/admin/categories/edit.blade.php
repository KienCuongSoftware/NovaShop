@extends('layouts.admin')

@section('title', 'Sửa danh mục')

@section('content')
<div class="page-header">
    <h2>Sửa danh mục</h2>
    <a class="btn btn-primary" href="{{ route('admin.categories.index') }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.categories.update', $category->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="name"><strong>Tên:</strong></label>
                <input type="text" name="name" id="name" value="{{ $category->name }}" class="form-control" placeholder="Nhập tên danh mục" required>
            </div>
            <hr>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Cập nhật</button>
            </div>
        </form>
    </div>
</div>
@endsection
