<div id="product-reviews-block" class="border-top pt-4 product-reviews-section" style="background:#fffbf8; border-radius: 10px; padding: 1.25rem;">
    <h6 class="font-weight-bold text-dark mb-3">ĐÁNH GIÁ SẢN PHẨM</h6>

    <style>
        .product-reviews-section { background:#fffbf8; border-radius: 10px; }
        .product-reviews-section .review-stars .star { font-size: 1.25rem; line-height: 1; }
        .product-reviews-section .review-filter-btn { white-space: nowrap; }
        .product-reviews-section .reviews-list-wrap { background: #ffffff; border-radius: 10px; padding: 1rem; }
        .product-reviews-section .reviews-list-wrap .review-item { background: #ffffff; }
        .product-reviews-section .review-item { border-top: 1px solid rgba(0,0,0,0.06); padding-top: 14px; background: #ffffff; border-radius: 8px; }
    </style>

    @php
        $reviewCountSafe = (int) ($reviewCount ?? 0);
        $avgRatingSafe = (float) ($avgRating ?? 0);
        $dist = $reviewDistribution ?? collect();
        if (!($dist instanceof \Illuminate\Support\Collection)) {
            $dist = collect($dist);
        }
        $getCnt = function($rating) use ($dist) {
            $cnt = $dist[(string)$rating] ?? $dist[$rating] ?? 0;
            return (int) $cnt;
        };
    @endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap: 1rem;">
        <div>
            <div class="d-flex align-items-center" style="gap: .5rem;">
                <div style="color:#d11a2a; font-weight:800; font-size: 1.75rem;">
                    {{ number_format($avgRatingSafe, 1, ',', '.') }}
                </div>
                <div class="text-muted" style="font-weight:600;">trên 5</div>
            </div>
            <div class="review-stars mt-1 d-flex" aria-label="{{ $avgRatingSafe }} trên 5">
                @for($i=1;$i<=5;$i++)
                    <span class="star {{ $i <= (int) floor($avgRatingSafe) ? 'text-warning' : 'text-muted' }}">
                        {!! $i <= (int) floor($avgRatingSafe) ? '&#9733;' : '&#9734;' !!}
                    </span>
                @endfor
            </div>
            <div class="text-muted small mt-1">
                ({{ $reviewCountSafe }} đánh giá)
            </div>
        </div>

        <div class="d-flex flex-wrap" style="gap: .5rem;">
            @php
                $activeRating = (int) request()->query('rating');
                if (!in_array($activeRating, [1,2,3,4,5], true)) $activeRating = null;
                $baseReviewParams = request()->except(['rating', 'page']);
                $baseReviewParams = is_array($baseReviewParams) ? $baseReviewParams : [];
                $allHref = route('products.show', $product) . '?' . http_build_query(array_merge($baseReviewParams, ['page' => 1]));
            @endphp

            <a href="{{ $allHref }}"
               class="btn btn-sm review-filter-btn {{ $activeRating === null ? 'btn-outline-danger' : 'btn-light' }}"
               style="border-width:2px;"
            >
                Tất Cả ({{ $reviewCountSafe }})
            </a>

            @foreach([5,4,3,2,1] as $r)
                @php
                    $cnt = $getCnt($r);
                    $isActive = $activeRating === (int)$r;
                    $href = route('products.show', $product) . '?' . http_build_query(array_merge($baseReviewParams, ['rating' => $r, 'page' => 1]));
                @endphp

                @if($cnt > 0)
                    <a href="{{ $href }}"
                       class="btn btn-sm review-filter-btn {{ $isActive ? 'btn-outline-danger' : 'btn-light' }}"
                       style="border-width:2px;"
                    >
                        {{ $r }} Sao ({{ number_format($cnt, 0, ',', '.') }})
                    </a>
                @else
                    <a href="javascript:void(0)"
                       class="btn btn-sm review-filter-btn btn-light text-muted"
                       style="border-width:2px;"
                       aria-disabled="true"
                    >
                        {{ $r }} Sao (0)
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    @if(Auth::check())
        <div class="mt-3" style="background:#fff; border-radius: 10px; border: 1px solid rgba(0,0,0,0.06); padding: 1rem;">
            <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
                <div class="font-weight-bold" style="font-size: 1.05rem;">Viết đánh giá sản phẩm</div>
                @if(isset($myReview) && $myReview && !($myReview->is_approved ?? false))
                    <span class="badge badge-warning" style="padding:.45rem .6rem;">Đang chờ duyệt</span>
                @endif
            </div>

            @if($errors->any())
                <div class="alert alert-danger py-2 mt-2 mb-2">
                    <div class="small font-weight-bold mb-1">Có lỗi:</div>
                    <ul class="mb-0 pl-3">
                        @foreach($errors->all() as $err)
                            <li class="small">{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                  action="{{ route('products.reviews.store', $product) }}"
                  enctype="multipart/form-data"
                  class="mt-2"
            >
                @csrf

                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label class="font-weight-bold mb-1">Số sao</label>
                        <select name="rating" class="form-control @error('rating') is-invalid @enderror" required>
                            @for($i=5;$i>=1;$i--)
                                @php $cur = old('rating', isset($myReview) && $myReview ? $myReview->rating : 5); @endphp
                                <option value="{{ $i }}" {{ (int)$cur === $i ? 'selected' : '' }}>{{ $i }} sao</option>
                            @endfor
                        </select>
                    </div>

                    <div class="form-group col-md-9">
                        <label class="font-weight-bold mb-1">Tiêu đề (tuỳ chọn)</label>
                        <input
                            type="text"
                            name="title"
                            class="form-control @error('title') is-invalid @enderror"
                            maxlength="255"
                            value="{{ old('title', isset($myReview) && $myReview ? $myReview->title : '') }}"
                            placeholder="VD: Rất hài lòng"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold mb-1">Nội dung</label>
                    <textarea
                        name="content"
                        class="form-control @error('content') is-invalid @enderror"
                        rows="4"
                        required
                        maxlength="2000"
                        placeholder="Chia sẻ trải nghiệm của bạn..."
                    >{{ old('content', isset($myReview) && $myReview ? $myReview->content : '') }}</textarea>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold mb-1">Phân loại hàng (tuỳ chọn)</label>
                        <input
                            type="text"
                            name="variant_classification"
                            class="form-control @error('variant_classification') is-invalid @enderror"
                            maxlength="255"
                            value="{{ old('variant_classification', isset($myReview) && $myReview ? $myReview->variant_classification : '') }}"
                            placeholder="VD: Màu Đen, Size M"
                        >
                    </div>

                    <div class="form-group col-md-6">
                        <label class="font-weight-bold mb-1">Ảnh (tối đa 5)</label>
                        <input
                            type="file"
                            name="images[]"
                            class="form-control @error('images.*') is-invalid @enderror"
                            accept="image/*"
                            multiple
                        >
                        <div class="small text-muted mt-1">Upload lại sẽ thay ảnh cũ.</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-danger font-weight-bold">
                    Gửi đánh giá
                </button>
            </form>
        </div>

        @if(isset($myReview) && $myReview && !($myReview->is_approved ?? false))
            <div class="mt-3" style="background:#fff; border-radius: 10px; border: 1px solid rgba(0,0,0,0.06); padding: 1rem;">
                <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
                    <div class="font-weight-bold">Đánh giá của bạn</div>
                    <span class="badge badge-warning" style="padding:.45rem .6rem;">Chờ duyệt</span>
                </div>

                @if(!empty($myReview->rejected_at))
                    <div class="alert alert-danger py-2 mt-2 mb-2">
                        Đánh giá của bạn đã bị từ chối{{ !empty($myReview->rejection_reason) ? ': '.$myReview->rejection_reason : '.' }}
                    </div>
                @endif

                <div class="review-stars d-flex mt-2" style="gap:2px;">
                    @for($i=1;$i<=5;$i++)
                        <span class="star {{ $i <= (int)$myReview->rating ? 'text-warning' : 'text-muted' }}">
                            {!! $i <= (int)$myReview->rating ? '&#9733;' : '&#9734;' !!}
                        </span>
                    @endfor
                </div>
                @if(!empty($myReview->title))
                    <div class="font-weight-bold mt-1">{{ $myReview->title }}</div>
                @endif
                <div class="text-secondary mt-1" style="white-space: pre-line;">{{ $myReview->content }}</div>

                @if(isset($myReview->images) && $myReview->images->count() > 0)
                    <div class="mt-2 d-flex flex-wrap gap-2">
                        @foreach($myReview->images as $img)
                            @php $filename = basename($img->path); @endphp
                            <a href="{{ asset('/images/reviews/'.$filename) }}" target="_blank" rel="noopener">
                                <img
                                    src="{{ asset('/images/reviews/'.$filename) }}"
                                    alt="Ảnh đánh giá"
                                    class="rounded"
                                    style="width: 68px; height: 68px; object-fit: cover; border:1px solid rgba(0,0,0,0.08);"
                                    loading="lazy"
                                >
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    @endif

    <div class="mt-4 reviews-list-wrap">
        @if(isset($reviews) && $reviews->count() > 0)
            @foreach($reviews as $rv)
                <div class="review-item mb-3">
                    <div class="d-flex align-items-start" style="gap: 12px;">
                        <div>
                            @if($rv->user?->avatar)
                                <img src="/images/avatars/{{ basename($rv->user->avatar) }}" alt="{{ $rv->user->name }}" class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover; border:1px solid rgba(0,0,0,0.08);">
                            @else
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-muted font-weight-bold" style="width:36px;height:36px;border:1px solid rgba(0,0,0,0.08);">
                                    {{ strtoupper(substr($rv->user->name ?? 'U', 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center flex-wrap" style="gap: .5rem;">
                                <strong>{{ $rv->user?->name ?? 'Người dùng' }}</strong>
                                <div class="review-stars d-flex" style="gap: 2px;">
                                    @for($i=1;$i<=5;$i++)
                                        <span class="star {{ $i <= (int)$rv->rating ? 'text-warning' : 'text-muted' }}">
                                            {!! $i <= (int)$rv->rating ? '&#9733;' : '&#9734;' !!}
                                        </span>
                                    @endfor
                                </div>
                            </div>
                            <div class="text-muted small mt-1">
                                {{ optional($rv->created_at)->format('Y-m-d H:i') }}
                                @if(!empty($rv->variant_classification))
                                    <span class="text-muted">| Phân loại hàng: {{ $rv->variant_classification }}</span>
                                @endif
                            </div>
                            @if(!empty($rv->title))
                                <div class="font-weight-bold mt-1">{{ $rv->title }}</div>
                            @endif
                            <div class="text-secondary mt-1" style="white-space: pre-line;">
                                {{ $rv->content }}
                            </div>
                            @if(isset($rv->images) && $rv->images->count() > 0)
                                <div class="mt-2 d-flex flex-wrap gap-2">
                                    @foreach($rv->images as $img)
                                        @php $filename = basename($img->path); @endphp
                                        <a href="{{ asset('/images/reviews/'.$filename) }}" target="_blank" rel="noopener">
                                            <img
                                                src="{{ asset('/images/reviews/'.$filename) }}"
                                                alt="Ảnh đánh giá"
                                                class="rounded"
                                                style="width: 68px; height: 68px; object-fit: cover; border:1px solid rgba(0,0,0,0.08);"
                                                loading="lazy"
                                            >
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="text-muted py-4">Chưa có đánh giá nào.</div>
        @endif
    </div>

    @if(isset($reviews) && $reviews->hasPages())
        @php
            $paginator = $reviews;
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

        <div class="mt-3 d-flex justify-content-center">
            <nav>
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

