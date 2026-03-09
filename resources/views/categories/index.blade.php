<!-- resources/views/categories/index.blade.php -->
@extends('layouts.app')

@section('title', 'Danh mục')

@section('content')
<div class="page-header">
    <h2>Danh mục</h2>
    <div>
        <a class="btn btn-outline-primary mr-2" href="{{ route('products.index') }}">Sản phẩm</a>
        <a class="btn btn-success" href="{{ route('categories.create') }}">Tạo danh mục mới</a>
    </div>
</div>

@if ($message = Session::get('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ $message }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th class="text-center" style="width: 80px;">ID</th>
                        <th style="width: 200px; max-width: 200px;">Tên</th>
                        <th class="text-center" style="width: 1%; white-space: nowrap;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                    <tr>
                        <td class="text-center align-middle">{{ $category->id }}</td>
                        <td class="align-middle">{{ $category->name }}</td>
                        <td class="text-center align-middle text-nowrap">
                            <a class="btn btn-info btn-sm" href="{{ route('categories.show', $category->id) }}">Xem</a>
                            <a class="btn btn-primary btn-sm" href="{{ route('categories.edit', $category->id) }}">Sửa</a>
                            <form id="delete-form-{{ $category->id }}" action="{{ route('categories.destroy', $category->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-danger btn-sm btn-delete" data-form-id="delete-form-{{ $category->id }}" data-name="{{ $category->name }}">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal xác nhận xóa -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="deleteModalLabel">Xác nhận xóa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Bạn có chắc muốn xóa danh mục <strong id="deleteCategoryName"></strong>?</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Xóa</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var deleteModal = document.getElementById('deleteModal');
    var deleteCategoryName = document.getElementById('deleteCategoryName');
    var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    var formToDelete = null;

    document.querySelectorAll('.btn-delete').forEach(function(btn) {
        btn.addEventListener('click', function() {
            formToDelete = document.getElementById(this.getAttribute('data-form-id'));
            deleteCategoryName.textContent = '"' + (this.getAttribute('data-name') || '') + '"';
            $(deleteModal).modal('show');
        });
    });

    confirmDeleteBtn.addEventListener('click', function() {
        if (formToDelete) {
            formToDelete.submit();
        }
        $(deleteModal).modal('hide');
    });
});
</script>
@endsection
