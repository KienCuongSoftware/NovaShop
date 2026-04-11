@extends('layouts.admin')

@section('title', 'Tổng quan')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
@endpush

@section('content')
<div class="dash-nova">
    <header class="dash-nova__hero">
        <div class="dash-nova__hero-main">
            <p class="dash-nova__eyebrow">NovaShop · Bảng điều khiển</p>
            <h1 class="dash-nova__title">Tổng quan cửa hàng</h1>
            <p class="dash-nova__lede">Theo dõi đơn hàng, doanh thu và hàng bán chạy trong một màn hình.</p>
        </div>
        <div class="dash-nova__hero-aside">
            <span class="dash-nova__clock">{{ now()->locale('vi')->translatedFormat('l, d/m/Y') }}</span>
            <p class="dash-nova__greet">Xin chào, <strong>{{ auth()->user()->name }}</strong></p>
            <a href="{{ route('admin.orders.index') }}" class="dash-nova__link-arrow">Đi tới đơn hàng →</a>
        </div>
    </header>

    <section class="dash-nova__kpis" aria-label="Chỉ số nhanh">
        <div class="dash-kpi">
            <span class="dash-kpi__label">Sản phẩm</span>
            <span class="dash-kpi__value">{{ number_format($stats['products']) }}</span>
        </div>
        <div class="dash-kpi">
            <span class="dash-kpi__label">Đơn hàng</span>
            <span class="dash-kpi__value">{{ number_format($stats['orders']) }}</span>
        </div>
        <div class="dash-kpi">
            <span class="dash-kpi__label">Khách hàng</span>
            <span class="dash-kpi__value">{{ number_format($stats['users']) }}</span>
        </div>
        <div class="dash-kpi">
            <span class="dash-kpi__label">Danh mục</span>
            <span class="dash-kpi__value">{{ number_format($stats['categories']) }}</span>
        </div>
        <div class="dash-kpi dash-kpi--accent">
            <span class="dash-kpi__label">Doanh thu (hoàn thành)</span>
            <span class="dash-kpi__value dash-kpi__value--money">{{ number_format($stats['revenue'], 0, ',', '.') }}₫</span>
        </div>
    </section>

    <div class="dash-nova__grid dash-nova__grid--charts2">
        <article class="dash-panel">
            <div class="dash-panel__head">
                <h2 class="dash-panel__title">Đơn theo trạng thái</h2>
                <p class="dash-panel__hint">Phân bổ đơn hiện có trong hệ thống.</p>
            </div>
            <div class="dash-panel__body dash-panel__body--chart">
                <canvas id="chartOrdersByStatus" aria-label="Biểu đồ tròn trạng thái đơn"></canvas>
            </div>
        </article>
        <article class="dash-panel">
            <div class="dash-panel__head">
                <h2 class="dash-panel__title">Khối lượng đơn 30 ngày</h2>
                <p class="dash-panel__hint">Số đơn được tạo mỗi ngày.</p>
            </div>
            <div class="dash-panel__body dash-panel__body--chart">
                <canvas id="chartOrdersLast30Days" aria-label="Biểu đồ cột đơn theo ngày"></canvas>
            </div>
        </article>
    </div>

    <div class="dash-nova__grid dash-nova__grid--split">
        <article class="dash-panel dash-panel--wide">
            <div class="dash-panel__head">
                <h2 class="dash-panel__title">Doanh thu theo ngày</h2>
                <p class="dash-panel__hint">30 ngày gần nhất · chỉ đơn <strong>Hoàn thành</strong> · theo ngày tạo đơn.</p>
            </div>
            <div class="dash-panel__body dash-panel__body--chart dash-panel__body--tall">
                <canvas id="chartRevenueByDay" aria-label="Biểu đồ doanh thu"></canvas>
            </div>
        </article>
        <aside class="dash-panel dash-panel--side">
            <div class="dash-panel__head">
                <h2 class="dash-panel__title">Tỷ lệ hủy</h2>
                <p class="dash-panel__hint">Đơn có trạng thái đã hủy so với tổng đơn tạo.</p>
            </div>
            <div class="dash-panel__body dash-cancel">
                <div class="dash-cancel__block">
                    <div class="dash-cancel__label">30 ngày gần nhất</div>
                    @if($cancelRate30Total > 0)
                        <div class="dash-cancel__ring-wrap">
                            <svg class="dash-cancel__ring" viewBox="0 0 36 36" aria-hidden="true">
                                <path class="dash-cancel__ring-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="dash-cancel__ring-fg" stroke-dasharray="{{ min(100, $cancelRate30Pct) }}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="dash-cancel__pct">{{ number_format($cancelRate30Pct, 1, ',', '.') }}<small>%</small></span>
                        </div>
                        <p class="dash-cancel__meta">{{ number_format($cancelRate30Cancelled) }} hủy · {{ number_format($cancelRate30Total) }} đơn trong kỳ</p>
                    @else
                        <p class="dash-cancel__empty">Chưa có đơn trong 30 ngày.</p>
                    @endif
                </div>
                <div class="dash-cancel__divider"></div>
                <div class="dash-cancel__block">
                    <div class="dash-cancel__label">Toàn thời gian</div>
                    @if($cancelRateAllTotal > 0)
                        <p class="dash-cancel__big">{{ number_format($cancelRateAllPct, 1, ',', '.') }}<span class="dash-cancel__pct-suffix">%</span></p>
                        <p class="dash-cancel__meta">{{ number_format($cancelRateAllCancelled) }} hủy · {{ number_format($cancelRateAllTotal) }} đơn</p>
                    @else
                        <p class="dash-cancel__empty">Chưa có đơn.</p>
                    @endif
                </div>
            </div>
        </aside>
    </div>

    <div class="dash-nova__grid dash-nova__grid--tables">
        <article class="dash-panel">
            <div class="dash-panel__head dash-panel__head--row">
                <div>
                    <h2 class="dash-panel__title">Hàng bán chạy</h2>
                    <p class="dash-panel__hint mb-0">Theo số lượng trên đơn hoàn thành (mọi thời gian).</p>
                </div>
            </div>
            <div class="dash-panel__body p-0">
                @if($topSkus->isEmpty())
                    <p class="dash-table-empty">Chưa có dữ liệu bán.</p>
                @else
                    <div class="dash-table-wrap">
                        <table class="dash-table dash-table--bestsellers">
                            <thead>
                                <tr>
                                    <th scope="col" class="dash-table__th--narrow">STT</th>
                                    <th scope="col">Sản phẩm</th>
                                    <th scope="col" class="dash-table__th--variant">Mã / biến thể</th>
                                    <th scope="col" class="dash-table__th--num dash-table__th--sold">Đã bán</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topSkus as $i => $row)
                                <tr>
                                    <td class="dash-table__muted">{{ $i + 1 }}</td>
                                    <td class="dash-table__cell--product">
                                        <a href="{{ route('admin.products.edit', $row['product_id']) }}" class="dash-table__link dash-table__name-ellipsis" title="{{ $row['name'] }}">{{ $row['name'] }}</a>
                                    </td>
                                    <td class="dash-table__cell--variant">
                                        @if(!empty($row['variant_display']))
                                            <span class="dash-pill dash-pill--variant" title="{{ $row['variant_display'] }}">{{ $row['variant_display'] }}</span>
                                        @elseif(!empty($row['sku']))
                                            <span class="dash-pill dash-pill--sku">{{ $row['sku'] }}</span>
                                        @elseif(!empty($row['product_variant_id']))
                                            <span class="dash-table__muted">Biến thể</span>
                                        @else
                                            <span class="dash-table__muted">Mặc định</span>
                                        @endif
                                    </td>
                                    <td class="dash-table__num dash-table__td--sold">{{ number_format($row['qty_sold']) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </article>

        <article class="dash-panel">
            <div class="dash-panel__head dash-panel__head--row">
                <div>
                    <h2 class="dash-panel__title">Đơn gần đây</h2>
                    <p class="dash-panel__hint mb-0">Năm đơn mới nhất.</p>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="dash-nova__btn-ghost">Tất cả đơn</a>
            </div>
            <div class="dash-panel__body p-0">
                @if($recentOrders->isEmpty())
                    <p class="dash-table-empty">Chưa có đơn hàng.</p>
                @else
                    <div class="dash-table-wrap dash-table-wrap--recent">
                        <table class="dash-table dash-table--recent">
                            <thead>
                                <tr>
                                    <th scope="col" class="dash-table__th--order-id">Mã</th>
                                    <th scope="col" class="dash-table__th--customer">Khách</th>
                                    <th scope="col" class="dash-table__th--num dash-table__th--total">Tổng</th>
                                    <th scope="col" class="dash-table__th--status">Trạng thái</th>
                                    <th scope="col" class="dash-table__th--time">Thời điểm</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentOrders as $order)
                                <tr>
                                    <td class="dash-table__td--order-id"><a href="{{ route('admin.orders.show', $order) }}" class="dash-table__link">#{{ $order->id }}</a></td>
                                    <td class="dash-table__cell--customer">
                                        @php $custName = $order->user->name ?? '—'; @endphp
                                        <span class="dash-table__ellipsis-inline" title="{{ $custName }}">{{ $custName }}</span>
                                    </td>
                                    <td class="dash-table__num dash-table__td--total"><span class="dash-table__ellipsis-inline" title="{{ number_format($order->total_amount, 0, ',', '.') }}₫">{{ number_format($order->total_amount, 0, ',', '.') }}₫</span></td>
                                    <td class="dash-table__td--status">
                                        @php
                                            $st = $order->status;
                                            $tone = $st === 'completed' ? 'ok' : (in_array($st, ['cancelled', 'return_refund'], true) ? 'muted' : 'wait');
                                        @endphp
                                        <span class="dash-tag dash-tag--{{ $tone }}">{{ \App\Models\Order::statusLabel($st) }}</span>
                                    </td>
                                    <td class="dash-table__muted dash-table__time dash-table__td--time">
                                        @php $orderTime = $order->created_at->format('d/m/Y · H:i'); @endphp
                                        <span class="dash-table__ellipsis-inline" title="{{ $orderTime }}">{{ $orderTime }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </article>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var font = { family: "'DM Sans', system-ui, sans-serif" };
    var gridColor = 'rgba(28, 25, 23, 0.06)';
    var tickColor = '#6b7280';

    /* Tông đỏ / cam / xám — tránh xanh lam hay teal */
    var doughnutPalette = ['#b71c1c', '#c62828', '#d32f2f', '#e53935', '#c2410c', '#b45309', '#6b7280', '#9ca3af'];

    var statusCtx = document.getElementById('chartOrdersByStatus');
    if (statusCtx) {
        var dataArr = @json($chartStatusData);
        var bg = dataArr.map(function(_, i) { return doughnutPalette[i % doughnutPalette.length]; });
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: @json($chartStatusLabels),
                datasets: [{
                    data: dataArr,
                    backgroundColor: bg,
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: font,
                            color: tickColor,
                            padding: 14,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    }
                }
            }
        });
    }

    var last30Ctx = document.getElementById('chartOrdersLast30Days');
    if (last30Ctx) {
        var grad = last30Ctx.getContext('2d').createLinearGradient(0, 0, 0, 280);
        grad.addColorStop(0, 'rgba(183, 28, 28, 0.85)');
        grad.addColorStop(1, 'rgba(183, 28, 28, 0.35)');
        new Chart(last30Ctx, {
            type: 'bar',
            data: {
                labels: @json($last30DaysLabels),
                datasets: [{
                    label: 'Số đơn',
                    data: @json($last30Days),
                    backgroundColor: grad,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: font, color: tickColor, maxRotation: 0 }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: font, color: tickColor },
                        grid: { color: gridColor }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
    }

    var revCtx = document.getElementById('chartRevenueByDay');
    if (revCtx) {
        new Chart(revCtx, {
            type: 'line',
            data: {
                labels: @json($revenueByDayLabels),
                datasets: [{
                    label: 'Doanh thu',
                    data: @json($revenueByDayValues),
                    fill: true,
                    backgroundColor: 'rgba(183, 28, 28, 0.08)',
                    borderColor: '#991b1b',
                    borderWidth: 2.5,
                    tension: 0.35,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#991b1b',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: font, color: tickColor, maxRotation: 0 }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: font,
                            color: tickColor,
                            callback: function(value) { return Number(value).toLocaleString('vi-VN'); }
                        },
                        grid: { color: gridColor }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return 'Doanh thu: ' + Number(ctx.parsed.y).toLocaleString('vi-VN') + '₫';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<style>
/* Scoped dashboard — không ảnh hưởng trang admin khác */
.dash-nova {
    --dash-bg: #ffffff;
    --dash-surface: #ffffff;
    --dash-ink: #1a1a1a;
    --dash-muted: #6b7280;
    --dash-line: rgba(0, 0, 0, 0.08);
    --dash-accent: #b71c1c;
    --dash-accent-soft: rgba(183, 28, 28, 0.1);
    font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
    color: var(--dash-ink);
    margin: -1.5rem -2rem 0;
    padding: 1.5rem 2rem 2.5rem;
    min-height: calc(100vh - 3rem);
    background: var(--dash-bg);
}

.dash-nova__hero {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1.5rem;
    padding: 1.75rem 1.5rem 1.75rem;
    margin-bottom: 1.25rem;
    background: linear-gradient(180deg, #c62828 0%, #b71c1c 55%, #a31515 100%);
    color: #ffffff;
    border-radius: 1rem;
    box-shadow: 0 10px 32px rgba(183, 28, 28, 0.28);
    border: 1px solid rgba(255, 255, 255, 0.12);
}

.dash-nova__eyebrow {
    font-size: 0.7rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.72);
    margin-bottom: 0.35rem;
}

.dash-nova__title {
    font-size: clamp(1.5rem, 2.5vw, 1.85rem);
    font-weight: 700;
    letter-spacing: -0.02em;
    margin: 0 0 0.35rem;
    line-height: 1.2;
}

.dash-nova__lede {
    margin: 0;
    max-width: 36rem;
    font-size: 0.95rem;
    color: rgba(255, 255, 255, 0.88);
    line-height: 1.5;
}

.dash-nova__hero-aside {
    text-align: left;
    width: 100%;
}

@media (min-width: 576px) {
    .dash-nova__hero-aside {
        width: auto;
        text-align: right;
    }
}

.dash-nova__clock {
    display: block;
    font-size: 0.75rem;
    text-transform: capitalize;
    color: rgba(255, 255, 255, 0.65);
    margin-bottom: 0.25rem;
}

.dash-nova__greet {
    margin: 0 0 0.5rem;
    font-size: 0.95rem;
    color: rgba(255, 255, 255, 0.95);
}

.dash-nova__link-arrow {
    font-size: 0.85rem;
    font-weight: 600;
    color: #ffffff;
    text-decoration: none;
    border-bottom: 1px solid rgba(255, 255, 255, 0.45);
    padding-bottom: 1px;
}
.dash-nova__link-arrow:hover {
    color: #ffffff;
    border-bottom-color: rgba(255, 255, 255, 0.9);
}

.dash-nova__kpis {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin-bottom: 1.25rem;
}

@media (min-width: 768px) {
    .dash-nova__kpis { grid-template-columns: repeat(3, 1fr); }
}
@media (min-width: 1200px) {
    .dash-nova__kpis { grid-template-columns: repeat(5, 1fr); }
}

.dash-kpi {
    background: var(--dash-surface);
    border: 1px solid var(--dash-line);
    border-radius: 0.75rem;
    padding: 1rem 1.1rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}

.dash-kpi--accent {
    background: #ffffff;
    border-color: rgba(183, 28, 28, 0.28);
    box-shadow: 0 0 0 1px rgba(183, 28, 28, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
}

.dash-kpi__label {
    display: block;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--dash-muted);
    margin-bottom: 0.35rem;
}

.dash-kpi__value {
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: -0.03em;
    line-height: 1.1;
}

.dash-kpi__value--money {
    font-size: 1.2rem;
    color: var(--dash-accent);
}

@media (min-width: 1200px) {
    .dash-kpi__value--money { font-size: 1.35rem; }
}

.dash-nova__grid {
    display: grid;
    gap: 1rem;
    margin-bottom: 1rem;
}

.dash-nova__grid--charts2 {
    grid-template-columns: 1fr;
}

@media (min-width: 992px) {
    .dash-nova__grid--charts2 { grid-template-columns: 1fr 1fr; }
}

.dash-nova__grid--split {
    grid-template-columns: 1fr;
}

@media (min-width: 992px) {
    .dash-nova__grid--split { grid-template-columns: minmax(0, 1.6fr) minmax(260px, 1fr); }
}

.dash-nova__grid--tables {
    grid-template-columns: 1fr;
}

@media (min-width: 1200px) {
    /* minmax(0,…) + min-width:0 trên panel để cột không ép tràn ngang */
    .dash-nova__grid--tables { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
}

.dash-nova__grid--tables > .dash-panel {
    min-width: 0;
}

.dash-panel {
    background: var(--dash-surface);
    border: 1px solid var(--dash-line);
    border-radius: 0.875rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.dash-panel__head {
    padding: 1rem 1.15rem 0.85rem;
    border-bottom: 1px solid var(--dash-line);
    background: linear-gradient(180deg, #f9fafb 0%, #ffffff 100%);
}

.dash-panel__head--row {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
}

.dash-panel__title {
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    margin: 0 0 0.2rem;
}

.dash-panel__hint {
    font-size: 0.8rem;
    color: var(--dash-muted);
    margin: 0;
    line-height: 1.45;
}

.dash-panel__body {
    padding: 1rem 1.15rem 1.15rem;
}

.dash-panel__body--chart {
    position: relative;
    height: 260px;
    padding: 0.75rem 0.85rem 1rem;
}

.dash-panel__body--tall { height: 300px; }

.dash-panel--side .dash-panel__body { padding: 1.15rem; }

.dash-nova__btn-ghost {
    display: inline-block;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--dash-accent);
    border: 1px solid rgba(183, 28, 28, 0.35);
    border-radius: 999px;
    padding: 0.35rem 0.9rem;
    text-decoration: none;
    white-space: nowrap;
    transition: background 0.15s, color 0.15s;
}
.dash-nova__btn-ghost:hover {
    background: var(--dash-accent);
    color: #fff;
    text-decoration: none;
}

/* Cancellation ring */
.dash-cancel__label {
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--dash-muted);
    margin-bottom: 0.75rem;
}

.dash-cancel__ring-wrap {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto 0.65rem;
}

.dash-cancel__ring {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.dash-cancel__ring-bg {
    fill: none;
    stroke: rgba(28, 25, 23, 0.08);
    stroke-width: 2.8;
}

.dash-cancel__ring-fg {
    fill: none;
    stroke: #6b7280;
    stroke-width: 2.8;
    stroke-linecap: round;
    transition: stroke-dasharray 0.5s ease;
}

.dash-cancel__pct {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    font-weight: 700;
    letter-spacing: -0.03em;
}

.dash-cancel__pct small {
    font-size: 0.65em;
    font-weight: 600;
    margin-left: 1px;
}

.dash-cancel__meta {
    font-size: 0.8rem;
    color: var(--dash-muted);
    text-align: center;
    margin: 0;
    line-height: 1.4;
}

.dash-cancel__big {
    font-size: 1.75rem;
    font-weight: 700;
    letter-spacing: -0.03em;
    margin: 0 0 0.25rem;
}

.dash-cancel__pct-suffix {
    font-size: 0.55em;
    font-weight: 600;
    color: var(--dash-muted);
    margin-left: 2px;
}

.dash-cancel__empty {
    font-size: 0.85rem;
    color: var(--dash-muted);
    margin: 0;
}

.dash-cancel__divider {
    height: 1px;
    background: var(--dash-line);
    margin: 1.15rem 0;
}

/* Tables */
.dash-table-wrap { overflow-x: auto; }

.dash-table-wrap--recent {
    overflow-x: hidden;
}

.dash-table {
    table-layout: fixed;
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.dash-table thead th {
    text-align: left;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--dash-muted);
    padding: 0.65rem 1rem;
    border-bottom: 1px solid var(--dash-line);
    background: #f9fafb;
}

.dash-table tbody tr {
    border-bottom: 1px solid var(--dash-line);
    transition: background 0.12s;
}

.dash-table tbody tr:hover { background: rgba(183, 28, 28, 0.03); }

.dash-table tbody td {
    padding: 0.75rem 1rem;
    vertical-align: middle;
}

.dash-table__th--narrow { width: 2.75rem; }

/* Hàng bán chạy: cột Đã bán hẹp, nhường chỗ cho tên SP / biến thể */
.dash-table--bestsellers .dash-table__th--narrow,
.dash-table--bestsellers tbody td:first-child {
    width: 2.25rem;
    padding-left: 0.65rem;
    padding-right: 0.35rem;
}

.dash-table--bestsellers .dash-table__th--variant,
.dash-table--bestsellers .dash-table__cell--variant {
    width: 30%;
}

.dash-table--bestsellers .dash-table__th--sold,
.dash-table--bestsellers .dash-table__td--sold {
    width: 3.1rem;
    min-width: 3.1rem;
    max-width: 3.5rem;
    padding-left: 0.35rem;
    padding-right: 0.45rem;
    white-space: nowrap;
    box-sizing: border-box;
}

.dash-table__cell--product { max-width: 0; }
.dash-table__name-ellipsis {
    display: block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.dash-table__th--num, .dash-table__num { text-align: right; }
.dash-table__num { font-weight: 600; font-variant-numeric: tabular-nums; }

.dash-table__ellipsis-inline {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Đơn gần đây: cột cố định theo %, ellipsis — tránh scrollbar ngang */
.dash-table--recent thead th,
.dash-table--recent tbody td {
    padding: 0.65rem 0.45rem;
}

.dash-table--recent .dash-table__th--order-id,
.dash-table--recent .dash-table__td--order-id {
    width: 10%;
}

.dash-table--recent .dash-table__th--customer,
.dash-table--recent .dash-table__cell--customer {
    width: 22%;
    overflow: hidden;
}

.dash-table--recent .dash-table__th--total,
.dash-table--recent .dash-table__td--total {
    width: 20%;
    overflow: hidden;
}

.dash-table--recent .dash-table__th--status,
.dash-table--recent .dash-table__td--status {
    width: 24%;
}

.dash-table--recent .dash-table__th--time,
.dash-table--recent .dash-table__td--time {
    width: 24%;
    white-space: normal;
    overflow: hidden;
}

.dash-table--recent .dash-table__td--status {
    overflow: hidden;
}

.dash-table--recent .dash-table__td--status .dash-tag {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    vertical-align: middle;
}

.dash-table__muted {
    color: var(--dash-muted);
    font-size: 0.82rem;
}

.dash-table__time { white-space: nowrap; }

.dash-table__link {
    color: var(--dash-ink);
    font-weight: 600;
    text-decoration: none;
    border-bottom: 1px solid transparent;
}
.dash-table__link:hover {
    color: var(--dash-accent);
    border-bottom-color: rgba(183, 28, 28, 0.35);
}

.dash-table-empty {
    text-align: center;
    color: var(--dash-muted);
    padding: 2rem 1rem;
    margin: 0;
    font-size: 0.9rem;
}

.dash-pill {
    display: inline-block;
    font-family: ui-monospace, 'Cascadia Code', monospace;
    font-size: 0.75rem;
    padding: 0.2rem 0.5rem;
    border-radius: 0.35rem;
}

.dash-pill--sku {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid var(--dash-line);
}

.dash-pill--variant {
    font-family: inherit;
    font-size: 0.8rem;
    font-weight: 500;
    line-height: 1.35;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    vertical-align: middle;
    background: #fafafa;
    color: var(--dash-ink);
    border: 1px solid var(--dash-line);
}

.dash-pill--ghost {
    background: transparent;
    color: var(--dash-muted);
    border: 1px dashed var(--dash-line);
}

.dash-tag {
    display: inline-block;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 0.25rem 0.55rem;
    border-radius: 0.35rem;
}

.dash-tag--ok {
    background: rgba(22, 163, 74, 0.12);
    color: #15803d;
}

.dash-tag--wait {
    background: rgba(217, 119, 6, 0.12);
    color: #c2410c;
}

.dash-tag--muted {
    background: rgba(107, 114, 128, 0.12);
    color: #4b5563;
}
</style>
@endsection
