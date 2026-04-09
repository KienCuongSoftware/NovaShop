@extends('layouts.user')

@section('title', 'So sánh sản phẩm - NovaShop')

@section('content')
<div class="page-header mb-4">
    <h2>So sánh (tối đa {{ \App\Http\Controllers\User\CompareController::MAX_ITEMS }})</h2>
    <div>
        <form action="{{ route('compare.share') }}" method="POST" class="d-inline-block mr-2 mb-2">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm">Chia sẻ so sánh</button>
        </form>
        @if($products->isNotEmpty())
        <form action="{{ route('compare.clear') }}" method="POST" class="d-inline" onsubmit="return bsConfirmSubmit(this, 'Xóa hết danh sách so sánh?');">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm">Xóa hết</button>
        </form>
        @endif
        <a href="{{ route('welcome') }}" class="btn btn-outline-secondary btn-sm">← Mua sắm</a>
    </div>
</div>

@if(session('share_link'))
    <div class="alert alert-info">
        <div class="font-weight-bold mb-1">Link chia sẻ:</div>
        <a href="{{ session('share_link') }}" target="_blank" rel="noopener">{{ session('share_link') }}</a>
    </div>
@endif

@if($products->isEmpty())
@include('partials.empty-state', [
    'type' => 'compare',
    'title' => 'Chưa có sản phẩm để so sánh',
    'message' => 'Trên trang sản phẩm bấm « So sánh » (tối đa 4 SP). Seeder NovaShopFeaturesSampleSeeder tạo sẵn danh sách mẫu cho mọi user thường.',
    'actionUrl' => route('welcome'),
    'actionLabel' => 'Xem sản phẩm',
])
@else
<div class="table-responsive">
    <table class="table table-bordered table-sm bg-white">
        <thead class="thead-light">
            <tr>
                <th style="min-width:120px;">Thuộc tính</th>
                @foreach($products as $p)
                <th style="min-width: 160px;">
                    <div class="mb-2">
                        @if($p->image)
                            <img src="/images/products/{{ basename($p->image) }}" alt="" class="img-fluid" style="max-height: 80px; object-fit: contain;">
                        @endif
                    </div>
                    <a href="{{ route('products.show', $p) }}">{{ Str::limit($p->name, 40) }}</a>
                    <form action="{{ route('compare.remove', $p) }}" method="POST" class="mt-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-link btn-sm p-0 text-danger">Bỏ</button>
                    </form>
                </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                <th>Giá từ</th>
                @foreach($products as $p)
                <td class="text-danger font-weight-bold">{{ number_format($p->effective_price, 0, ',', '.') }}₫</td>
                @endforeach
            </tr>
            <tr>
                <th>Danh mục</th>
                @foreach($products as $p)
                <td>{{ $p->category?->name ?? '—' }}</td>
                @endforeach
            </tr>
            <tr>
                <th>Thương hiệu</th>
                @foreach($products as $p)
                <td>{{ $p->brand?->name ?? '—' }}</td>
                @endforeach
            </tr>
            <tr>
                <th>Tồn kho</th>
                @foreach($products as $p)
                <td>{{ number_format($p->effective_stock, 0, ',', '.') }}</td>
                @endforeach
            </tr>
            @foreach($attributeNames as $attrName)
            <tr>
                <th>{{ $attrName }}</th>
                @foreach($products as $p)
                @php
                    $vals = [];
                    foreach ($p->variants as $v) {
                        foreach ($v->attributeValues as $av) {
                            if ($av->attribute->name === $attrName) {
                                $vals[$av->value] = true;
                            }
                        }
                    }
                    $str = count($vals) ? implode(', ', array_keys($vals)) : '—';
                @endphp
                <td>{{ $str }}</td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
