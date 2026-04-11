@extends('layouts.staff')

@section('title', 'Nhập/xuất kho')

@section('content')
<div class="page-header">
    <h2>Nhập/xuất kho</h2>
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <form action="{{ route('staff.inventory-logs.index') }}" method="GET" class="admin-search-form d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
            <select name="type" class="form-control" style="max-width: 140px;">
                <option value="">Loại: Tất cả</option>
                <option value="import" {{ ($type ?? '') === 'import' ? 'selected' : '' }}>Nhập</option>
                <option value="export" {{ ($type ?? '') === 'export' ? 'selected' : '' }}>Xuất</option>
                <option value="adjust" {{ ($type ?? '') === 'adjust' ? 'selected' : '' }}>Điều chỉnh</option>
            </select>
            <input type="text" name="q" class="form-control" style="max-width: 220px;" placeholder="Nguồn, ghi chú, tên SP, ID đơn..." value="{{ $q ?? '' }}">
            <button type="submit" class="btn btn-primary">Lọc</button>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 60px;">STT</th>
                        <th style="width: 140px;">Thời gian</th>
                        <th style="width: 90px;">Loại</th>
                        <th>Sản phẩm / Biến thể</th>
                        <th class="text-right" style="width: 100px;">Số lượng</th>
                        <th style="width: 100px;">Nguồn</th>
                        <th style="width: 90px;">Đơn hàng</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    @php $stt = ($logs->currentPage() - 1) * $logs->perPage(); @endphp
                    @forelse($logs as $log)
                    <tr>
                        <td class="align-middle text-muted">{{ ++$stt }}</td>
                        <td class="align-middle small">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td class="align-middle">
                            @if($log->type === 'import')
                                <span class="badge badge-success">Nhập</span>
                            @elseif($log->type === 'export')
                                <span class="badge badge-danger">Xuất</span>
                            @else
                                <span class="badge badge-secondary">Điều chỉnh</span>
                            @endif
                        </td>
                        <td class="align-middle">
                            @php $logProduct = $log->product ?? $log->productVariant?->product; @endphp
                            @if($logProduct)
                                <span>{{ $logProduct->name }}</span>
                                @if($log->productVariant)
                                    <br><small class="text-muted">{{ $log->productVariant->display_name }}</small>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="align-middle text-right font-weight-bold {{ $log->type === 'export' ? 'text-danger' : ($log->type === 'import' ? 'text-success' : '') }}">
                            {{ $log->type === 'export' ? '-' : '+' }}{{ $log->quantity }}
                        </td>
                        <td class="align-middle small">{{ $log->source ?? '—' }}</td>
                        <td class="align-middle small">
                            @if($log->order_id && $log->order)
                                <a href="{{ route('staff.orders.show', $log->order) }}">#{{ $log->order_id }}</a>
                            @elseif($log->order_id)
                                #{{ $log->order_id }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="align-middle small text-muted">{{ Str::limit($log->note, 50) ?: '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Chưa có bản ghi nhập/xuất kho.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
    <div class="card-footer">
        @php
            $paginator = $logs;
            $current = $paginator->currentPage();
            $last = $paginator->lastPage();
            $elements = [];
            if ($last <= 6) {
                for ($i = 1; $i <= $last; $i++) { $elements[] = $i; }
            } else {
                $start = max(1, $current - 2);
                $end = min($last, $start + 5);
                if ($end - $start < 5) { $start = max(1, $end - 5); }
                if ($start > 1) { $elements = [1, '...']; }
                for ($i = $start; $i <= $end; $i++) { $elements[] = $i; }
                if ($end < $last) { $elements[] = '...'; $elements[] = $last; }
            }
        @endphp
        <nav class="d-flex justify-content-center">
            <ul class="pagination mb-0">
                <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                    @if($paginator->onFirstPage())<span class="page-link">&lsaquo;</span>
                    @else<a class="page-link" href="{{ $paginator->previousPageUrl() }}">&lsaquo;</a>@endif
                </li>
                @foreach($elements as $el)
                    @if($el === '...')
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    @else
                        <li class="page-item {{ (int)$el === (int)$current ? 'active' : '' }}">
                            @if((int)$el === (int)$current)<span class="page-link">{{ $el }}</span>
                            @else<a class="page-link" href="{{ $paginator->url($el) }}">{{ $el }}</a>@endif
                        </li>
                    @endif
                @endforeach
                <li class="page-item {{ !$paginator->hasMorePages() ? 'disabled' : '' }}">
                    @if(!$paginator->hasMorePages())<span class="page-link">&rsaquo;</span>
                    @else<a class="page-link" href="{{ $paginator->nextPageUrl() }}">&rsaquo;</a>@endif
                </li>
            </ul>
        </nav>
    </div>
    @endif
</div>
@endsection
