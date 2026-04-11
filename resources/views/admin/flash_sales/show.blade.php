@extends('layouts.admin')

@section('title', 'Chi tiết Flash Sale')

@section('content')
<div class="page-header">
    <h2>{{ $flash_sale->name }}</h2>
    <div class="admin-toolbar">
        <a href="{{ route('admin.flash-sales.edit', $flash_sale) }}" class="btn btn-primary">Sửa</a>
        <a href="{{ route('admin.flash-sales.index') }}" class="btn btn-outline-secondary">← Danh sách</a>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <p><strong>Bắt đầu:</strong> {{ $flash_sale->start_time->format('d/m/Y H:i') }}</p>
        <p><strong>Kết thúc:</strong> {{ $flash_sale->end_time->format('d/m/Y H:i') }}</p>
        <p><strong>Trạng thái:</strong>
            @php $ds = $flash_sale->derivedStatus(); @endphp
            @if($ds === 'active')
                <span class="badge badge-success">Đang diễn ra</span>
            @elseif($ds === 'scheduled')
                <span class="badge badge-info">Sắp diễn ra</span>
            @else
                <span class="badge badge-secondary">Đã kết thúc</span>
            @endif
        </p>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header font-weight-bold">Sản phẩm ({{ $flash_sale->items->count() }})</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Sản phẩm / Biến thể</th>
                        <th>Giá sale</th>
                        <th>Số lượng</th>
                        <th>Đã bán</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($flash_sale->items as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->productVariant->product->name ?? '—' }}</strong>
                            <br><small class="text-muted">{{ $item->productVariant->display_name ?? '—' }}</small>
                        </td>
                        <td>{{ number_format($item->sale_price, 0, ',', '.') }}₫</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->sold }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
