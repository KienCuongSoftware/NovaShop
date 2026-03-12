@extends('layouts.admin')

@section('title', 'Sản phẩm')

@section('content')
<div class="page-header">
    <h2>Sản phẩm</h2>
    <div>
        <a class="btn btn-outline-primary mr-2" href="{{ route('admin.categories.index') }}">Danh mục</a>
        <a class="btn btn-success" href="{{ route('admin.products.create') }}">Thêm sản phẩm</a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th class="text-center" style="width: 60px;">STT</th>
                        <th style="width: 160px;">Tên</th>
                        <th style="width: 120px;">Danh mục</th>
                        <th class="text-right" style="width: 90px;">Giá cũ</th>
                        <th class="text-right" style="width: 90px;">Giá mới</th>
                        <th class="text-center" style="width: 80px;">Số lượng</th>
                        <th class="text-center" style="width: 80px;">Hình ảnh</th>
                        <th class="text-center" style="width: 1%; white-space: nowrap;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                    <tr>
                        <td class="text-center align-middle">{{ ($products->currentPage() - 1) * $products->perPage() + $loop->iteration }}</td>
                        <td class="align-middle">{{ $product->name }}</td>
                        <td class="align-middle">{{ $product->category->name ?? '—' }}</td>
                        <td class="text-right align-middle small text-muted">
                            @if($product->old_price !== null)
                                <span style="text-decoration: line-through;">{{ number_format($product->old_price, 0, ',', '.') }}₫</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-right align-middle"><strong class="text-danger" style="font-size: 1.05rem;">{{ number_format($product->price, 0, ',', '.') }}₫</strong></td>
                        <td class="text-center align-middle">{{ $product->quantity }}</td>
                        <td class="text-center align-middle">
                            @if($product->image)
                                <img src="/images/products/{{ basename($product->image) }}" alt="{{ $product->name }}" class="img-thumbnail" style="max-height: 40px; max-width: 50px; object-fit: cover;" loading="lazy" width="50" height="40" onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2240%22 height=%2240%22 viewBox=%220 0 24 24%22 fill=%22%23ddd%22%3E%3Crect width=%2224%22 height=%2224%22/%3E%3C/svg%3E';">
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center align-middle text-nowrap">
                            <a class="btn btn-info btn-sm" href="{{ route('admin.products.show', $product->id) }}">Xem</a>
                            <a class="btn btn-primary btn-sm" href="{{ route('admin.products.edit', $product->id) }}">Sửa</a>
                            <form id="delete-form-{{ $product->id }}" action="{{ route('admin.products.destroy', $product->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-danger btn-sm btn-delete" data-form-id="delete-form-{{ $product->id }}" data-name="{{ $product->name }}">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Chưa có sản phẩm nào.</td>
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
                $start = max(1, min($current - 2, $last - 5));
                $elements = [$start, $start + 1, $start + 2, '...', $start + 3, $start + 4, $start + 5];
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
