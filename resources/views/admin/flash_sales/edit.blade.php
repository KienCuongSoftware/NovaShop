@extends('layouts.admin')

@section('title', 'Sửa Flash Sale')

@php
    $normDt = function (?string $v): string {
        if ($v === null || $v === '') {
            return '';
        }

        return str_replace('T', ' ', $v);
    };
    $fpStart = $normDt(old('start_time', $flash_sale->start_time->format('Y-m-d H:i')));
    $fpEnd = $normDt(old('end_time', $flash_sale->end_time->format('Y-m-d H:i')));
@endphp

@section('content')
<div class="page-header">
    <h2>Sửa: {{ $flash_sale->name }}</h2>
    <a href="{{ route('admin.flash-sales.index') }}" class="btn btn-outline-secondary">← Danh sách</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card shadow-sm mb-4">
    <div class="card-header font-weight-bold">Thông tin chương trình</div>
    <div class="card-body">
        <form action="{{ route('admin.flash-sales.update', $flash_sale) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="name">Tên chương trình <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $flash_sale->name) }}" required>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="start_time">Thời gian bắt đầu <span class="text-danger">*</span></label>
                        <input type="text" name="start_time" id="start_time" class="form-control" value="{{ $fpStart }}" placeholder="dd/mm/yyyy hh:mm" autocomplete="off" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="end_time">Thời gian kết thúc <span class="text-danger">*</span></label>
                        <input type="text" name="end_time" id="end_time" class="form-control" value="{{ $fpEnd }}" placeholder="dd/mm/yyyy hh:mm" autocomplete="off" required>
                    </div>
                </div>
            </div>
            @php $derived = $flash_sale->derivedStatus(); @endphp
            <p class="mb-3"><strong>Trạng thái (theo thời gian):</strong>
                @if($derived === 'active')
                    <span class="badge badge-success">Đang diễn ra</span>
                @elseif($derived === 'scheduled')
                    <span class="badge badge-info">Sắp diễn ra</span>
                @else
                    <span class="badge badge-secondary">Đã kết thúc</span>
                @endif
            </p>
            <p class="text-muted small">Không thể đặt thời gian bắt đầu về quá khứ (trừ khi giữ nguyên giá trị hiện tại). Thời gian kết thúc phải sau bắt đầu và sau &quot;bây giờ&quot;, trừ khi bạn giữ nguyên khi chương trình đã kết thúc.</p>
            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header font-weight-bold d-flex justify-content-between align-items-center">
        <span>Sản phẩm trong Flash Sale ({{ $flash_sale->items->count() }})</span>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.flash_sales.items.store', $flash_sale) }}" method="POST" class="mb-4">
            @csrf
            <div class="form-row align-items-end">
                <div class="col-md-5">
                    <label for="product_variant_id">Chọn biến thể sản phẩm</label>
                    <select name="product_variant_id" id="product_variant_id" class="form-control" required>
                        <option value="">-- Chọn sản phẩm (biến thể) --</option>
                        @foreach($variantsForSelect as $productId => $variants)
                            @php $product = $variants->first()->product; @endphp
                            <optgroup label="{{ $product->name }}">
                                @foreach($variants as $v)
                                    <option value="{{ $v->id }}" data-price="{{ $v->price }}">
                                        {{ $v->display_name }} — Giá gốc: {{ number_format($v->price, 0, ',', '.') }}₫
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @if($variantsForSelect->isEmpty())
                        <small class="text-muted">Tất cả biến thể đã được thêm hoặc chưa có sản phẩm.</small>
                    @endif
                </div>
                <div class="col-md-2">
                    <label for="sale_price">Giá sale (₫)</label>
                    <input type="number" name="sale_price" id="sale_price" class="form-control" min="0" step="1" value="{{ old('sale_price') }}" required title="Không được lớn hơn giá gốc biến thể">
                </div>
                <div class="col-md-2">
                    <label for="quantity">Số lượng</label>
                    <input type="number" name="quantity" id="quantity" class="form-control" min="1" value="{{ old('quantity', 1) }}" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success" {{ $variantsForSelect->isEmpty() ? 'disabled' : '' }}>Thêm</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Sản phẩm / Biến thể</th>
                        <th>Giá gốc</th>
                        <th>Giá sale</th>
                        <th>Số lượng</th>
                        <th>Đã bán</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($flash_sale->items as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->productVariant->product->name ?? '—' }}</strong>
                            <br><small class="text-muted">{{ $item->productVariant->display_name ?? '—' }}</small>
                        </td>
                        <td>{{ number_format($item->productVariant->price ?? 0, 0, ',', '.') }}₫</td>
                        <td>{{ number_format($item->sale_price, 0, ',', '.') }}₫</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->sold }}</td>
                        <td class="text-nowrap">
                            <form action="{{ route('admin.flash_sales.items.destroy', [$flash_sale, $item]) }}" method="POST" class="d-inline" onsubmit="return bsConfirmSubmit(this, 'Xóa sản phẩm này khỏi Flash Sale?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-3">Chưa có sản phẩm. Thêm biến thể ở form trên.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('admin.flash_sales._flatpickr_24h', ['fpMode' => 'edit'])

<script>
document.getElementById('product_variant_id')?.addEventListener('change', function() {
    var opt = this.options[this.selectedIndex];
    var saleInput = document.getElementById('sale_price');
    if (!saleInput) return;
    if (opt && opt.value && opt.dataset.price) {
        saleInput.value = opt.dataset.price;
        saleInput.setAttribute('max', opt.dataset.price);
    } else {
        saleInput.removeAttribute('max');
    }
});
</script>
@endsection
