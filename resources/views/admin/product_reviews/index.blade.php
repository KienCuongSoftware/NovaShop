@extends('layouts.admin')

@section('title', 'Duyệt đánh giá sản phẩm')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <h2 class="mb-0">Duyệt đánh giá sản phẩm</h2>
    <a class="btn btn-outline-primary" href="{{ route('admin.dashboard') }}">Quay lại</a>
</div>

<div class="card mt-3">
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success py-2">
                {{ session('success') }}
            </div>
        @endif

        @if($reviews->count() === 0)
            <div class="text-muted">Không có đánh giá chờ duyệt.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" style="background:#fff;">
                    <thead class="thead-light">
                        <tr>
                            <th>Người dùng</th>
                            <th>Sản phẩm</th>
                            <th>Số sao</th>
                            <th>Nội dung</th>
                            <th>Ảnh</th>
                            <th>Thời gian</th>
                            <th class="text-right" style="min-width:220px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reviews as $rv)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center" style="gap:10px;">
                                        @if($rv->user?->avatar)
                                            <img
                                                src="/images/avatars/{{ basename($rv->user->avatar) }}"
                                                alt=""
                                                style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:1px solid rgba(0,0,0,0.08);"
                                            >
                                        @else
                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-muted font-weight-bold"
                                                 style="width:34px;height:34px;border:1px solid rgba(0,0,0,0.08);">
                                                {{ strtoupper(substr($rv->user->name ?? 'U', 0, 1)) }}
                                            </div>
                                        @endif
                                        <div>
                                            <div class="font-weight-bold">{{ $rv->user?->name ?? '—' }}</div>
                                            <div class="text-muted small">{{ $rv->user?->email ?? '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-weight-bold">{{ $rv->product?->name ?? '—' }}</div>
                                    <div class="small text-muted">{{ $rv->variant_classification ?? '' }}</div>
                                </td>
                                <td>
                                    <div class="review-stars d-flex" style="gap:2px;">
                                        @for($i=1;$i<=5;$i++)
                                            <span class="star {{ $i <= (int)$rv->rating ? 'text-warning' : 'text-muted' }}">
                                                {!! $i <= (int)$rv->rating ? '&#9733;' : '&#9734;' !!}
                                            </span>
                                        @endfor
                                    </div>
                                </td>
                                <td>
                                    @if(!empty($rv->title))
                                        <div class="font-weight-bold">{{ $rv->title }}</div>
                                    @endif
                                    <div style="white-space:pre-line;">{{ \Illuminate\Support\Str::limit($rv->content, 160) }}</div>
                                </td>
                                <td>
                                    @if($rv->images->count() > 0)
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($rv->images->take(3) as $img)
                                                @php $filename = basename($img->path); @endphp
                                                <a href="{{ asset('/images/reviews/'.$filename) }}" target="_blank" rel="noopener">
                                                    <img
                                                        src="{{ asset('/images/reviews/'.$filename) }}"
                                                        alt=""
                                                        style="width:46px;height:46px;object-fit:cover;border-radius:8px;border:1px solid rgba(0,0,0,0.08);"
                                                    >
                                                </a>
                                            @endforeach
                                            @if($rv->images->count() > 3)
                                                <span class="text-muted small">+{{ $rv->images->count() - 3 }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-muted small">
                                    {{ optional($rv->created_at)->format('Y-m-d H:i') }}
                                </td>
                                <td class="text-right">
                                    <form action="{{ route('admin.product-reviews.approve', $rv) }}" method="POST" style="display:inline-block;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success font-weight-bold mb-1">Duyệt</button>
                                    </form>

                                    <form action="{{ route('admin.product-reviews.reject', $rv) }}" method="POST" style="display:inline-block;">
                                        @csrf
                                        <div class="form-group mb-2" style="min-width:240px;">
                                            <input
                                                type="text"
                                                name="reason"
                                                class="form-control form-control-sm"
                                                placeholder="Lý do (tuỳ chọn)"
                                                maxlength="500"
                                            >
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-outline-danger font-weight-bold mb-1">Từ chối</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $reviews->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

