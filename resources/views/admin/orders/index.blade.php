@extends('layouts.admin')

@section('title', 'Đơn hàng')

@section('content')
<div class="page-header">
    <h2>Đơn hàng</h2>
</div>

<ul class="nav nav-tabs mb-2">
    <li class="nav-item">
        <a class="nav-link {{ ($status ?? 'all') === 'all' ? 'active' : '' }}" href="{{ route('admin.orders.index', ['status' => 'all', 'shipping_status' => $shippingStatus ?? 'all', 'q' => $q ?? '']) }}">Tất cả</a>
    </li>
    @foreach(\App\Models\Order::tabStatusKeys() as $key)
    <li class="nav-item">
        <a class="nav-link {{ ($status ?? '') === $key ? 'active' : '' }}" href="{{ route('admin.orders.index', ['status' => $key, 'shipping_status' => $shippingStatus ?? 'all', 'q' => $q ?? '']) }}">{{ \App\Models\Order::statusLabel($key) }}</a>
    </li>
    @endforeach
</ul>
<ul class="nav nav-tabs mb-3" style="border-bottom: 1px solid #dee2e6;">
    <li class="nav-item">
        <a class="nav-link {{ ($shippingStatus ?? 'all') === 'all' ? 'active' : '' }}" href="{{ route('admin.orders.index', ['status' => $status ?? 'all', 'shipping_status' => 'all', 'q' => $q ?? '']) }}" style="font-size: 0.9rem;">Vận chuyển: Tất cả</a>
    </li>
    @foreach(\App\Models\Order::tabShippingStatusKeys() as $key)
    <li class="nav-item">
        <a class="nav-link {{ ($shippingStatus ?? '') === $key ? 'active' : '' }}" href="{{ route('admin.orders.index', ['status' => $status ?? 'all', 'shipping_status' => $key, 'q' => $q ?? '']) }}" style="font-size: 0.9rem;">{{ \App\Models\Order::shippingStatusLabel($key) }}</a>
    </li>
    @endforeach
</ul>

<div class="card mb-3">
    <div class="card-body py-3">
        <form action="{{ route('admin.orders.index') }}" method="GET" class="admin-search-form d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
            <input type="hidden" name="status" value="{{ $status ?? 'all' }}">
            <input type="hidden" name="shipping_status" value="{{ $shippingStatus ?? 'all' }}">
            <input type="text" name="q" class="form-control" style="max-width: 280px;" placeholder="ID đơn, SĐT, địa chỉ, tên/email khách..." value="{{ $q ?? '' }}">
            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 90px;">ID đơn</th>
                        <th>Khách hàng</th>
                        <th style="width: 140px;">Ngày đặt</th>
                        <th class="text-right" style="width: 120px;">Tổng tiền</th>
                        <th style="width: 120px;">Trạng thái</th>
                        <th style="width: 110px;">Vận chuyển</th>
                        <th style="width: 100px;">Thanh toán</th>
                        <th style="width: 100px;" class="text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td class="align-middle">
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-weight-bold">#{{ $order->id }}</a>
                        </td>
                        <td class="align-middle">
                            <div>{{ $order->user->name ?? '—' }}</div>
                            <small class="text-muted">{{ $order->user->email ?? '' }}</small>
                        </td>
                        <td class="align-middle small">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td class="align-middle text-right">{{ number_format($order->total_amount, 0, ',', '.') }}₫</td>
                        <td class="align-middle">
                            <span class="badge badge-{{ $order->status === 'completed' ? 'success' : (in_array($order->status, ['cancelled', 'return_refund'], true) ? 'secondary' : 'warning') }}">
                                {{ \App\Models\Order::statusLabel($order->status) }}
                            </span>
                        </td>
                        <td class="align-middle small text-muted">
                            {{ \App\Models\Order::shippingStatusLabel((string) ($order->shipping_status ?? \App\Models\Order::SHIPPING_STATUS_PENDING)) }}
                        </td>
                        <td class="align-middle small">
                            {{ $order->payment_method === 'paypal' ? 'PayPal' : 'COD' }}
                            @if($order->payment_status === 'paid')
                                <span class="badge badge-success">Đã TT</span>
                            @elseif(in_array($order->status, ['unpaid', 'payment_failed'], true))
                                <span class="badge badge-secondary">Chưa TT</span>
                            @endif
                        </td>
                        <td class="align-middle text-right">
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Không có đơn hàng nào.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($orders->hasPages())
    <div class="card-footer">
        @php
            $paginator = $orders;
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
