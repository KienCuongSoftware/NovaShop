@php
    $preview = $productShippingPreview ?? null;
@endphp
@if($preview)
@php
    $fmtDay = static function ($d) {
        return $d->format('j').' Th'.$d->format('m');
    };
    $from = $preview['date_from'];
    $to = $preview['date_to'];
    $fee = (int) ($preview['fee'] ?? 0);
@endphp
<div class="product-shipping-estimate mb-3">
    <div class="product-shipping-estimate__inner">
        <div class="product-shipping-estimate__label text-muted">Vận chuyển</div>
        <div class="product-shipping-estimate__body">
            <div class="product-shipping-estimate__line1">
                <span class="product-shipping-estimate__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                </span>
                <span class="product-shipping-estimate__dates">
                    Nhận trong <strong>{{ $fmtDay($from) }}</strong> - <strong>{{ $fmtDay($to) }}</strong>
                </span>
                <a href="{{ route('checkout.show') }}" class="product-shipping-estimate__chevron text-muted" title="Thanh toán">›</a>
            </div>
            <div class="product-shipping-estimate__line2">
                @if($fee <= 0)
                    Phí ship <span class="font-weight-bold text-success">0₫</span>
                @else
                    Phí ship <span class="font-weight-bold">{{ number_format($fee, 0, ',', '.') }}₫</span>
                @endif
                @if($preview['distance_km'] !== null)
                    <span class="text-muted small">· {{ number_format((float) $preview['distance_km'], 1, ',', '.') }} km</span>
                @endif
            </div>
            @if(!empty($preview['hint']))
                <p class="product-shipping-estimate__hint small text-muted mb-0 mt-1">{{ $preview['hint'] }}</p>
            @endif
        </div>
    </div>
</div>
<style>
.product-shipping-estimate {
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 4px;
    background: #fafafa;
    font-size: 0.9rem;
}
.product-shipping-estimate__inner {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem 1rem;
    padding: 0.65rem 0.85rem;
}
.product-shipping-estimate__label {
    flex: 0 0 auto;
    width: 5.5rem;
    padding-top: 0.15rem;
    font-size: 0.8rem;
    line-height: 1.35;
}
.product-shipping-estimate__body {
    flex: 1;
    min-width: 0;
}
.product-shipping-estimate__line1 {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex-wrap: wrap;
    line-height: 1.35;
}
.product-shipping-estimate__icon {
    flex-shrink: 0;
    color: #1ba8a8;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.product-shipping-estimate__dates {
    flex: 1;
    min-width: 0;
    color: #222;
    font-size: 0.9rem;
}
.product-shipping-estimate__chevron {
    flex-shrink: 0;
    font-size: 1.15rem;
    line-height: 1;
    text-decoration: none;
    opacity: 0.55;
    margin-left: 0.15rem;
}
.product-shipping-estimate__chevron:hover {
    opacity: 1;
    color: #dc3545 !important;
    text-decoration: none;
}
.product-shipping-estimate__line2 {
    margin-top: 0.2rem;
    padding-left: 1.55rem;
    font-size: 0.85rem;
    color: #333;
    line-height: 1.4;
}
@media (max-width: 576px) {
    .product-shipping-estimate__inner {
        flex-direction: column;
        align-items: stretch;
    }
    .product-shipping-estimate__label {
        width: auto;
        padding-top: 0;
    }
    .product-shipping-estimate__line2 {
        padding-left: 0;
    }
}
</style>
@endif
