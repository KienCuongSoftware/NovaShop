@extends('layouts.admin')

@section('title', 'Sản phẩm')

@section('content')
<div class="page-header">
    <h2>Sản phẩm</h2>
    <a class="btn btn-success" href="{{ route('admin.products.create') }}">+ Thêm sản phẩm</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th class="text-center" style="width: 50px;">STT</th>
                        <th style="width: 160px; max-width: 160px;">Sản phẩm</th>
                        <th style="width: 130px;">Danh mục</th>
                        <th class="text-right" style="width: 90px;">Giá cũ</th>
                        <th class="text-right" style="width: 90px;">Giá mới</th>
                        <th class="text-center" style="width: 70px;">SL</th>
                        <th class="text-center" style="width: 180px; white-space: nowrap;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                    <tr>
                        <td class="text-center align-middle text-muted">{{ ($products->currentPage() - 1) * $products->perPage() + $loop->iteration }}</td>
                        <td class="align-middle" style="max-width: 160px;">
                            <div class="d-flex align-items-center">
                                @if($product->image)
                                    <img src="/images/products/{{ basename($product->image) }}" alt="" class="rounded mr-2 flex-shrink-0" style="width: 32px; height: 32px; object-fit: cover;" loading="lazy">
                                @else
                                    <div class="rounded bg-light mr-2 flex-shrink-0 d-flex align-items-center justify-content-center text-muted" style="width: 32px; height: 32px; font-size: 0.9rem;">📦</div>
                                @endif
                                <span class="text-truncate d-inline-block" style="max-width: 110px;" title="{{ $product->name }}">{{ Str::limit($product->name, 22) }}</span>
                            </div>
                        </td>
                        <td class="align-middle"><span class="badge badge-light">{{ $product->category->name ?? '—' }}</span></td>
                        <td class="text-right align-middle">
                            @if($product->old_price !== null)
                                <span class="text-muted small" style="text-decoration: line-through;">{{ number_format($product->old_price, 0, ',', '.') }}₫</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-right align-middle"><strong class="text-danger">{{ number_format($product->price, 0, ',', '.') }}₫</strong></td>
                        <td class="text-center align-middle">{{ number_format($product->quantity, 0, ',', '.') }}</td>
                        <td class="text-center align-middle text-nowrap">
                            <a class="btn btn-outline-info btn-sm" href="{{ route('admin.products.show', $product) }}">Xem</a>
                            <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.products.edit', $product) }}">Sửa</a>
                            <form id="delete-form-{{ $product->id }}" action="{{ route('admin.products.destroy', $product) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-outline-danger btn-sm btn-delete" data-form-id="delete-form-{{ $product->id }}" data-name="{{ $product->name }}">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">Chưa có sản phẩm nào.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($products->hasPages())
    <div class="card-footer">
        @php
            $paginator = $products;
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
</div>

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
                <p class="mb-0">Bạn có chắc muốn xóa sản phẩm <strong id="deleteItemName"></strong>?</p>
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
    var deleteItemName = document.getElementById('deleteItemName');
    var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    var formToDelete = null;
    document.querySelectorAll('.btn-delete').forEach(function(btn) {
        btn.addEventListener('click', function() {
            formToDelete = document.getElementById(this.getAttribute('data-form-id'));
            deleteItemName.textContent = '"' + (this.getAttribute('data-name') || '') + '"';
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
