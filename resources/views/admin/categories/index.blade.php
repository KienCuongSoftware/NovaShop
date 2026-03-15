@extends('layouts.admin')

@section('title', 'Danh mục')

@section('content')
<style>
.cat-list-card { border-left: 4px solid #dc3545; transition: box-shadow 0.2s; }
.cat-list-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.cat-parent-row { background: #fafafa; }
.cat-child-item { padding: 0.35rem 0.5rem; border-radius: 4px; margin-bottom: 0.25rem; background: #fff; border: 1px solid #eee; }
.cat-child-item:hover { background: #f8f9fa; }
.cat-child-item .btn-group .btn { padding: 0.2rem 0.5rem; font-size: 0.75rem; }
.cat-badge { font-size: 0.7rem; }
</style>
<div class="page-header">
    <h2>Danh mục</h2>
    <div class="admin-toolbar">
        <form method="GET" action="{{ route('admin.categories.index') }}" class="admin-search-form mb-0">
            <div class="input-group" style="max-width: 320px;">
                <input type="text" name="q" class="form-control" placeholder="Tìm theo tên danh mục..." value="{{ $q ?? '' }}">
                <div class="input-group-append">
                    <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                </div>
            </div>
        </form>
        <a class="btn btn-success" href="{{ route('admin.categories.create') }}">+ Tạo danh mục mới</a>
    </div>
</div>

<div class="list-group">
    @forelse ($categories as $category)
    <div class="card cat-list-card mb-3">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-auto pr-0">
                    @if($category->image)
                        <img src="/images/categories/{{ basename($category->image) }}" alt="{{ $category->name }}" class="rounded" style="width: 48px; height: 48px; object-fit: cover;">
                    @else
                        <div class="rounded bg-light d-flex align-items-center justify-content-center text-muted" style="width: 48px; height: 48px; font-size: 1.5rem;">📁</div>
                    @endif
                </div>
                <div class="col">
                    <div class="d-flex align-items-center flex-wrap">
                        <h6 class="mb-0 font-weight-bold">{{ $category->name }}</h6>
                        @if($category->children->isNotEmpty())
                            <span class="badge badge-secondary cat-badge ml-2">{{ $category->children->count() }} danh mục con</span>
                        @endif
                    </div>
                    @if($category->children->isNotEmpty())
                        <div class="mt-2">
                            @foreach($category->children as $child)
                                <div class="cat-child-item d-flex align-items-center justify-content-between flex-wrap">
                                    <span class="text-dark">└ {{ $child->name }}</span>
                                    <span>
                                        <a class="btn btn-outline-info btn-sm" href="{{ route('admin.categories.show', $child) }}">Xem</a>
                                        <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.categories.edit', $child) }}">Sửa</a>
                                        <form id="delete-form-{{ $child->id }}" action="{{ route('admin.categories.destroy', $child) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-outline-danger btn-sm btn-delete" data-form-id="delete-form-{{ $child->id }}" data-name="{{ $child->name }}">Xóa</button>
                                        </form>
                                    </span>
                                </div>
                                @if($child->children->isNotEmpty())
                                    @foreach($child->children as $leaf)
                                        <div class="cat-child-item d-flex align-items-center justify-content-between flex-wrap ml-3">
                                            <span class="text-muted">├ {{ $leaf->name }}</span>
                                            <span>
                                                <a class="btn btn-outline-info btn-sm" href="{{ route('admin.categories.show', $leaf) }}">Xem</a>
                                                <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.categories.edit', $leaf) }}">Sửa</a>
                                                <form id="delete-form-{{ $leaf->id }}" action="{{ route('admin.categories.destroy', $leaf) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-outline-danger btn-sm btn-delete" data-form-id="delete-form-{{ $leaf->id }}" data-name="{{ $leaf->name }}">Xóa</button>
                                                </form>
                                            </span>
                                        </div>
                                    @endforeach
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="col-auto text-right">
                    <a class="btn btn-info btn-sm" href="{{ route('admin.categories.show', $category) }}">Xem</a>
                    <a class="btn btn-primary btn-sm" href="{{ route('admin.categories.edit', $category) }}">Sửa</a>
                    <form id="delete-form-{{ $category->id }}" action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-danger btn-sm btn-delete" data-form-id="delete-form-{{ $category->id }}" data-name="{{ $category->name }}">Xóa</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="card-body text-center text-muted py-5">Chưa có danh mục nào.</div>
    </div>
    @endforelse
</div>

@if ($categories->hasPages())
    <div class="mt-3 d-flex justify-content-center">
        @php
            $paginator = $categories;
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
        if (formToDelete) formToDelete.submit();
        $(deleteModal).modal('hide');
    });
});
</script>
@endsection
