@extends('layouts.admin')

@section('title', 'Trang quản trị')

@section('content')
<div class="page-header">
    <h2>Trang quản trị</h2>
</div>

<div class="card mb-4">
    <div class="card-body py-3">
        <p class="mb-0">Chào mừng <strong>{{ auth()->user()->name }}</strong> đến trang quản trị.</p>
        <p class="text-muted small mt-2 mb-0">Bạn đang đăng nhập với quyền admin.</p>
    </div>
</div>

{{-- Thẻ thống kê tổng quan --}}
<div class="row mb-4">
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="card border-0 shadow-sm h-100 dashboard-stat-card dashboard-stat-products">
            <div class="card-body d-flex align-items-center">
                <div class="dashboard-stat-icon mr-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><polyline points="4 8 10 8 10 16"/></svg>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">Sản phẩm</div>
                    <div class="h4 mb-0 font-weight-bold">{{ number_format($stats['products']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="card border-0 shadow-sm h-100 dashboard-stat-card dashboard-stat-orders">
            <div class="card-body d-flex align-items-center">
                <div class="dashboard-stat-icon mr-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">Đơn hàng</div>
                    <div class="h4 mb-0 font-weight-bold">{{ number_format($stats['orders']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="card border-0 shadow-sm h-100 dashboard-stat-card dashboard-stat-users">
            <div class="card-body d-flex align-items-center">
                <div class="dashboard-stat-icon mr-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">Khách hàng</div>
                    <div class="h4 mb-0 font-weight-bold">{{ number_format($stats['users']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="card border-0 shadow-sm h-100 dashboard-stat-card dashboard-stat-categories">
            <div class="card-body d-flex align-items-center">
                <div class="dashboard-stat-icon mr-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">Danh mục</div>
                    <div class="h4 mb-0 font-weight-bold">{{ number_format($stats['categories']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="card border-0 shadow-sm h-100 dashboard-stat-card dashboard-stat-revenue">
            <div class="card-body d-flex align-items-center">
                <div class="dashboard-stat-icon mr-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">Doanh thu (đã hoàn thành)</div>
                    <div class="h4 mb-0 font-weight-bold text-danger">{{ number_format($stats['revenue'], 0, ',', '.') }}₫</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Biểu đồ --}}
<div class="row mb-4">
    <div class="col-lg-6 mb-4 mb-lg-0">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">Đơn hàng theo trạng thái</h5>
            </div>
            <div class="card-body">
                <div class="position-relative" style="height: 280px;">
                    <canvas id="chartOrdersByStatus"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">Đơn hàng 30 ngày gần nhất</h5>
            </div>
            <div class="card-body">
                <div class="position-relative" style="height: 280px;">
                    <canvas id="chartOrdersLast30Days"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Đơn hàng mới nhất --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom">
        <h5 class="mb-0">Đơn hàng mới nhất</h5>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-danger">Xem tất cả</a>
    </div>
    <div class="card-body p-0">
        @if($recentOrders->isEmpty())
            <p class="text-muted text-center py-4 mb-0">Chưa có đơn hàng.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Khách hàng</th>
                            <th class="text-right">Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Ngày đặt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $order)
                        <tr>
                            <td><a href="{{ route('admin.orders.show', $order) }}">#{{ $order->id }}</a></td>
                            <td>{{ $order->user->name ?? '—' }}</td>
                            <td class="text-right font-weight-bold">{{ number_format($order->total_amount, 0, ',', '.') }}₫</td>
                            <td><span class="badge badge-{{ $order->status === 'completed' ? 'success' : (in_array($order->status, ['cancelled', 'return_refund'], true) ? 'secondary' : 'warning') }}">{{ \App\Models\Order::statusLabel($order->status) }}</span></td>
                            <td class="small text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var statusCtx = document.getElementById('chartOrdersByStatus');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: @json($chartStatusLabels),
                datasets: [{
                    data: @json($chartStatusData),
                    backgroundColor: @json($chartStatusColorsOrdered),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    var last30Ctx = document.getElementById('chartOrdersLast30Days');
    if (last30Ctx) {
        new Chart(last30Ctx, {
            type: 'bar',
            data: {
                labels: @json($last30DaysLabels),
                datasets: [{
                    label: 'Số đơn',
                    data: @json($last30Days),
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: '#dc3545',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
});
</script>

<style>
.dashboard-stat-card { border-radius: 0.5rem; }
.dashboard-stat-icon { width: 56px; height: 56px; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; }
.dashboard-stat-products .dashboard-stat-icon { background: rgba(40, 167, 69, 0.15); color: #28a745; }
.dashboard-stat-orders .dashboard-stat-icon { background: rgba(0, 123, 255, 0.15); color: #007bff; }
.dashboard-stat-users .dashboard-stat-icon { background: rgba(111, 66, 193, 0.15); color: #6f42c1; }
.dashboard-stat-categories .dashboard-stat-icon { background: rgba(23, 162, 184, 0.15); color: #17a2b8; }
.dashboard-stat-revenue .dashboard-stat-icon { background: rgba(220, 53, 69, 0.15); color: #dc3545; }
</style>
@endsection
