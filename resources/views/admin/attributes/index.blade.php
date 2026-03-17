@extends('layouts.admin')

@section('title', 'Thuộc tính')

@section('content')
<div class="page-header">
    <h2>Thuộc tính sản phẩm</h2>
    <div class="admin-toolbar">
        <a class="btn btn-success" href="{{ route('admin.attributes.create') }}">+ Thêm thuộc tính</a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>ID</th>
                    <th>Tên thuộc tính</th>
                    <th>Số giá trị</th>
                    <th style="width: 240px; min-width: 240px;" class="text-right">Thao tác</th>
bb                </tr>
            </thead>
            <tbody>
                @forelse ($attributes as $attr)
                <tr>
                    <td class="align-middle">{{ $attr->id }}</td>
                    <td class="align-middle font-weight-bold">{{ $attr->name }}</td>
                    <td class="align-middle text-muted">{{ $attr->attribute_values_count ?? 0 }}</td>
                    <td class="align-middle text-right">
                        <div class="d-flex flex-nowrap justify-content-end">
                            <a class="btn btn-outline-primary btn-sm mr-1" href="{{ route('admin.attributes.edit', $attr) }}">Sửa / Quản lý giá trị</a>
                            <button type="button" class="btn btn-outline-danger btn-sm btn-delete" data-form-id="delete-form-{{ $attr->id }}" data-name="{{ $attr->name }}">Xóa</button>
                        </div>
                        <form id="delete-form-{{ $attr->id }}" action="{{ route('admin.attributes.destroy', $attr) }}" method="POST" class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-5">Chưa có thuộc tính nào. Thêm thuộc tính (vd: Màu sắc, Size, Loại) để dùng cho biến thể sản phẩm.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($attributes->isNotEmpty())
<script>
document.querySelectorAll('.btn-delete').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var name = this.getAttribute('data-name');
        if (confirm('Bạn có chắc muốn xóa thuộc tính “‘ + name + ’”? Các giá trị thuộc tính cũng sẽ bị xóa.')) {
            document.getElementById(this.getAttribute('data-form-id')).submit();
        }
    });
});
</script>
@endif
@endsection
