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
                    <th style="width: 70px;">STT</th>
                    <th>Tên thuộc tính</th>
                    <th>Số giá trị</th>
                    <th style="width: 240px; min-width: 240px;" class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attributes as $attr)
                <tr>
                    <td class="align-middle text-muted">{{ ($attributes->currentPage() - 1) * $attributes->perPage() + $loop->iteration }}</td>
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

@if ($attributes->hasPages())
<div class="mt-3 d-flex justify-content-center">
    @php
        $paginator = $attributes;
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
