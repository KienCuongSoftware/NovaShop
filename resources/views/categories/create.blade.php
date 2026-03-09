<!-- resources/views/categories/create.blade.php -->
<!-- Trang thêm danh mục (Create Categories) -->
@extends('layouts.app')

@section('title', 'Tạo danh mục mới')

@section('content')
<div class="page-header">
    <h2>Tạo danh mục mới</h2>
    <a class="btn btn-primary" href="{{ route('categories.index') }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('categories.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="name"><strong>Tên:</strong></label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Nhập tên danh mục" required>
            </div>
            <hr>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>
@endsection
