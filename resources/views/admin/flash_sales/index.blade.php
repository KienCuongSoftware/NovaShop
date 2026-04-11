@extends('layouts.admin')

@section('title', 'Flash Sale')

@section('content')
<div class="page-header">
    <h2>Flash Sale</h2>
    <div class="admin-toolbar">
        <a class="btn btn-success" href="{{ route('admin.flash-sales.create') }}">+ Tạo chương trình</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 70px;">STT</th>
                        <th>Tên</th>
                        <th>Bắt đầu</th>
                        <th>Kết thúc</th>
                        <th>Trạng thái</th>
                        <th>Số SP</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($flashSales as $fs)
                    <tr>
                        <td>{{ ($flashSales->currentPage() - 1) * $flashSales->perPage() + $loop->iteration }}</td>
                        <td>{{ $fs->name }}</td>
                        <td>{{ $fs->start_time->format('d/m/Y H:i') }}</td>
                        <td>{{ $fs->end_time->format('d/m/Y H:i') }}</td>
                        <td>
                            @php $ds = $fs->derivedStatus(); @endphp
                            @if($ds === 'active')
                                <span class="badge badge-success">Đang diễn ra</span>
                            @elseif($ds === 'scheduled')
                                <span class="badge badge-info">Sắp diễn ra</span>
                            @else
                                <span class="badge badge-secondary">Đã kết thúc</span>
                            @endif
                        </td>
                        <td>
                            <div><strong>{{ $fs->products_count ?? 0 }}</strong> SP</div>
                            <div class="text-muted small">{{ $fs->items_count }} biến thể</div>
                        </td>
                        <td class="text-nowrap">
                            <a href="{{ route('admin.flash-sales.show', $fs) }}" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                            <a href="{{ route('admin.flash-sales.edit', $fs) }}" class="btn btn-sm btn-outline-secondary">Sửa</a>
                            <form action="{{ route('admin.flash-sales.destroy', $fs) }}" method="POST" class="d-inline" onsubmit="return bsConfirmSubmit(this, 'Xóa chương trình này?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Chưa có chương trình Flash Sale.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($flashSales->hasPages())
    <div class="card-footer">
        @php
            $paginator = $flashSales;
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
        <nav class="d-flex justify-content-center">
            <ul class="pagination mb-0">
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
@endsection
