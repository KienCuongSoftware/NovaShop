@extends('layouts.admin')

@section('title', 'Thương hiệu')

@section('content')
<div class="page-header">
    <h2>Thương hiệu</h2>
    <a class="btn btn-success" href="{{ route('admin.brands.create') }}">+ Tạo thương hiệu mới</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width: 60px;">Logo</th>
                    <th>Tên</th>
                    <th>Slug</th>
                    <th style="width: 100px;">Sản phẩm</th>
                    <th style="width: 180px;" class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($brands as $brand)
                <tr>
                    <td class="align-middle">
                        @if($brand->logo)
                            <img src="/images/brands/{{ basename($brand->logo) }}" alt="{{ $brand->name }}" class="rounded" style="width: 48px; height: 48px; object-fit: contain; background: #f8f9fa;">
                        @else
                            <div class="rounded bg-light d-flex align-items-center justify-content-center text-muted" style="width: 48px; height: 48px; font-size: 1.25rem;">🏷️</div>
                        @endif
                    </td>
                    <td class="align-middle font-weight-bold">{{ $brand->name }}</td>
                    <td class="align-middle text-muted">{{ $brand->slug }}</td>
                    <td class="align-middle text-muted">{{ $brand->products_count ?? 0 }}</td>
                    <td class="align-middle text-right">
                        <a class="btn btn-outline-info btn-sm" href="{{ route('admin.brands.show', $brand) }}">Xem</a>
                        <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.brands.edit', $brand) }}">Sửa</a>
                        <button type="button" class="btn btn-outline-danger btn-sm btn-delete" data-form-id="delete-form-{{ $brand->id }}" data-name="{{ $brand->name }}">Xóa</button>
                        <form id="delete-form-{{ $brand->id }}" action="{{ route('admin.brands.destroy', $brand) }}" method="POST" class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-5">Chưa có thương hiệu nào.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($brands->hasPages())
<div class="mt-3 d-flex justify-content-center">
    @php
        $paginator = $brands;
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $elements = [];
        if ($last <= 6) {
            for ($i = 1; $i <= $last; $i++) { $elements[] = $i; }
        } else {
            $start = max(1, $current - 2);
            $end = min($last, $start + 5);
            if ($end - $start < 5) {
                $start = max(1, $end - 5);
            }
            $elements = [];
            if ($start > 1) {
                $elements = [1, '...'];
            }
            for ($i = $start; $i <= $end; $i++) {
                $elements[] = $i;
            }
            if ($end < $last) {
                $elements[] = '...';
                $elements[] = $last;
            }
        }
    @endphp
    <nav>
        <ul class="pagination">
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                @if($paginator->onFirstPage())
                    <span class="page-link">&lsaquo;</span>
                @else
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}">&lsaquo;</a>
                @endif
            </li>
            @foreach($elements as $el)
                @if($el === '...')
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                @else
                    <li class="page-item {{ (int)$el === (int)$current ? 'active' : '' }}">
                        @if((int)$el === (int)$current)
                            <span class="page-link">{{ $el }}</span>
                        @else
                            <a class="page-link" href="{{ $paginator->url($el) }}">{{ $el }}</a>
                        @endif
                    </li>
                @endif
            @endforeach
            <li class="page-item {{ !$paginator->hasMorePages() ? 'disabled' : '' }}">
                @if(!$paginator->hasMorePages())
                    <span class="page-link">&rsaquo;</span>
                @else
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}">&rsaquo;</a>
                @endif
            </li>
        </ul>
    </nav>
</div>
@endif

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
                <p class="mb-0">Bạn có chắc muốn xóa thương hiệu <strong id="deleteBrandName"></strong>?</p>
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
    var deleteBrandName = document.getElementById('deleteBrandName');
    var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    var formToDelete = null;
    document.querySelectorAll('.btn-delete').forEach(function(btn) {
        btn.addEventListener('click', function() {
            formToDelete = document.getElementById(this.getAttribute('data-form-id'));
            deleteBrandName.textContent = '"' + (this.getAttribute('data-name') || '') + '"';
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
