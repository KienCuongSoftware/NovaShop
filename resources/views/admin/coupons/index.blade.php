@extends('layouts.admin')

@section('title', 'Mã giảm giá')

@section('content')
<div class="page-header">
    <h2>Mã giảm giá / Voucher</h2>
    <a class="btn btn-success" href="{{ route('admin.coupons.create') }}">+ Tạo mã mới</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Mã</th>
                    <th>Loại</th>
                    <th>Giá trị</th>
                    <th>Tối thiểu</th>
                    <th>Danh mục</th>
                    <th>Hiệu lực</th>
                    <th>Dùng</th>
                    <th class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($coupons as $c)
                <tr>
                    <td class="font-weight-bold">{{ $c->code }}</td>
                    <td>{{ $c->discount_type === 'percent' ? '%' : 'Cố định' }}</td>
                    <td>{{ $c->discount_type === 'percent' ? $c->discount_value.'%' : number_format($c->discount_value, 0, ',', '.').'₫' }}</td>
                    <td>{{ number_format($c->min_order_amount, 0, ',', '.') }}₫</td>
                    <td>{{ $c->category?->name ?? 'Toàn shop' }}</td>
                    <td>
                        @if($c->is_active && $c->isCurrentlyValid())
                            <span class="badge badge-success">OK</span>
                        @else
                            <span class="badge badge-secondary">Off</span>
                        @endif
                    </td>
                    <td>{{ $c->uses_count }}{{ $c->max_uses ? ' / '.$c->max_uses : '' }}</td>
                    <td class="text-right">
                        <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.coupons.edit', $c) }}">Sửa</a>
                        <button type="button" class="btn btn-outline-danger btn-sm btn-delete" data-form-id="del-c-{{ $c->id }}" data-name="{{ $c->code }}">Xóa</button>
                        <form id="del-c-{{ $c->id }}" action="{{ route('admin.coupons.destroy', $c) }}" method="POST" class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">Chưa có mã.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($coupons->hasPages())
<div class="mt-3 d-flex justify-content-center">{{ $coupons->links() }}</div>
@endif

<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="deleteModalLabel">Xác nhận xóa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Bạn có chắc muốn xóa mã <strong id="deleteCouponName"></strong>?</p>
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
    var deleteName = document.getElementById('deleteCouponName');
    var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    var formToDelete = null;
    document.querySelectorAll('.btn-delete').forEach(function(btn) {
        btn.addEventListener('click', function() {
            formToDelete = document.getElementById(this.getAttribute('data-form-id'));
            deleteName.textContent = '"' + (this.getAttribute('data-name') || '') + '"';
            $(deleteModal).modal('show');
        });
    });
    confirmDeleteBtn.addEventListener('click', function() {
        if (formToDelete) formToDelete.submit();
        $(deleteModal).modal('hide');
    });
});
</script>
@endsection
