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

