@extends('layouts.user')

@section('title', 'So sánh chia sẻ - NovaShop')

@section('content')
<div class="page-header mb-4">
    <h2 class="mb-0">So sánh (chia sẻ)</h2>
    <div class="text-muted small mt-2">
        Link: <a href="{{ route('share.compare.show', ['token' => $share->token]) }}" target="_blank" rel="noopener">{{ route('share.compare.show', ['token' => $share->token]) }}</a>
    </div>
    <div class="mt-3">
        <a href="{{ route('welcome') }}" class="btn btn-outline-secondary btn-sm">← Về trang chủ</a>
    </div>
</div>

@if($products->isEmpty())
    @include('partials.empty-state', [
        'type' => 'compare_share',
        'title' => 'Không có sản phẩm để so sánh',
        'message' => 'Danh sách so sánh này hiện đang rỗng.',
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
                        <th style="min-width:160px;">
                            <div class="mb-2">
                                @if($p->image)
                                    <img src="/images/products/{{ basename($p->image) }}" alt="" class="img-fluid" style="max-height: 80px; object-fit: contain;">
                                @endif
                            </div>
                            <div class="font-weight-bold">{{ $p->name }}</div>
                            <div class="text-danger font-weight-bold">{{ number_format($p->effective_price, 0, ',', '.') }}₫</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th>Tồn kho</th>
                    @foreach($products as $p)
                        <td>{{ number_format($p->effective_stock, 0, ',', '.') }}</td>
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
                @foreach($attributeNames as $attrName)
                    <tr>
                        <th>{{ $attrName }}</th>
                        @foreach($products as $p)
                            <td>
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
                                {{ $str }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection

