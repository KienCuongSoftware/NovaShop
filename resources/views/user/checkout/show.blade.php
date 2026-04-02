@extends('layouts.user')

@section('title', 'Xác nhận đơn hàng - NovaShop')

@section('content')
<div class="page-header mb-4">
    <h2>Xác nhận đơn hàng</h2>
    <a href="{{ route('cart.index') }}" class="btn btn-outline-secondary">← Giỏ hàng</a>
</div>

<form action="{{ route('checkout.place-order') }}" method="POST" id="checkout-form">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4 checkout-shipping-card">
                <div class="card-header"><strong>Thông tin giao hàng</strong></div>
                <div class="card-body">
                    @if($addresses->isNotEmpty())
                    <div class="form-group mb-4">
                        <label class="d-block mb-2">Chọn địa chỉ</label>
                        <div class="border rounded p-3 mb-3" style="max-height: 220px; overflow-y: auto;">
                            @foreach($addresses as $addr)
                            <div class="custom-control custom-radio mb-2">
                                <input type="radio" name="address_id" id="addr_{{ $addr->id }}" value="{{ $addr->id }}" class="custom-control-input address-option" data-lat="{{ $addr->lat ?? '' }}" data-lng="{{ $addr->lng ?? '' }}" {{ old('address_id') == $addr->id || ($loop->first && $addr->is_default) ? 'checked' : '' }}>
                                <label class="custom-control-label w-100" for="addr_{{ $addr->id }}">
                                    @if($addr->label)<span class="badge badge-secondary">{{ $addr->label }}</span> @endif
                                    @if($addr->is_default)<span class="badge badge-danger">Mặc định</span> @endif
                                    <strong>{{ $addr->full_name }}</strong> · {{ $addr->phone }}<br>
                                    <small class="text-muted">{{ $addr->full_address }}</small>
                                </label>
                            </div>
                            @endforeach
                            <div class="custom-control custom-radio mb-0">
                                <input type="radio" name="address_id" id="addr_new" value="" class="custom-control-input address-option" {{ old('address_id') === null && !old('address_id') ? '' : (old('address_id') === '' ? 'checked' : '') }}>
                                <label class="custom-control-label" for="addr_new">Nhập địa chỉ mới (chọn trên bản đồ)</label>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div id="new-address-fields" class="{{ $addresses->isNotEmpty() ? 'd-none' : '' }}">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Họ tên <span class="text-danger">*</span></label>
                                    <input type="text" name="full_name" id="full_name" class="form-control" value="{{ old('full_name', $user->name ?? '') }}" placeholder="Nguyễn Văn A">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Số điện thoại <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone') }}" required placeholder="0912345678">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Địa chỉ giao hàng <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <input type="text" id="address" name="shipping_address" class="form-control" value="{{ old('shipping_address') }}" placeholder="Tìm kiếm hoặc chọn trên bản đồ" autocomplete="off">
                                <div id="address-suggest-dropdown" class="d-none bg-white border rounded shadow-sm position-absolute w-100" style="z-index: 1000; max-height: 200px; overflow-y: auto; top: 100%; left: 0; right: 0;"></div>
                            </div>
                            <small class="form-text text-muted">Gõ địa chỉ để gợi ý, hoặc nhấn vào bản đồ / kéo marker.</small>
                        </div>
                        <input type="hidden" name="lat" id="lat" value="{{ old('lat') }}">
                        <input type="hidden" name="lng" id="lng" value="{{ old('lng') }}">
                        @include('partials.leaflet-address-picker', ['mapId' => 'checkout-map', 'showGeolocate' => true])
                    </div>

                    <div class="form-group mb-0">
                        <label>Ghi chú</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Ghi chú cho đơn hàng">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header"><strong>Phương thức thanh toán</strong></div>
                <div class="card-body">
                    <div class="form-group mb-0">
                        <div class="custom-control custom-radio mb-2">
                            <input type="radio" id="pm_cod" name="payment_method" value="cod" class="custom-control-input" {{ old('payment_method', 'cod') === 'cod' ? 'checked' : '' }}>
                            <label class="custom-control-label" for="pm_cod">COD (Thanh toán khi nhận hàng)</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="pm_paypal" name="payment_method" value="paypal" class="custom-control-input" {{ old('payment_method') === 'paypal' ? 'checked' : '' }}>
                            <label class="custom-control-label" for="pm_paypal">PayPal</label>
                        </div>
                        <div class="custom-control custom-radio mt-2">
                            <input type="radio" id="pm_momo" name="payment_method" value="momo" class="custom-control-input" {{ old('payment_method') === 'momo' ? 'checked' : '' }}>
                            <label class="custom-control-label" for="pm_momo">MoMo</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><strong>Đơn hàng</strong></div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($cart->items as $item)
                        @php
                            $fi = ($activeFlashSale ?? null) && $item->product_variant_id ? $activeFlashSale->items->firstWhere('product_variant_id', $item->product_variant_id) : null;
                            $unitP = $fi && $fi->remaining > 0 ? (float) $fi->sale_price : ($item->productVariant ? (float) $item->productVariant->price : (float) $item->product->price);
                            $lineTotal = $unitP * $item->quantity;
                        @endphp
                        <li class="list-group-item d-flex align-items-center">
                            @if($item->product->image)
                                <img src="/images/products/{{ basename($item->product->image) }}" alt="" class="rounded mr-3" style="width: 50px; height: 50px; object-fit: cover;">
                            @else
                                <div class="rounded mr-3 bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">—</div>
                            @endif
                            <div class="flex-grow-1">
                                <div class="font-weight-bold">{{ $item->product->name }}</div>
                                @if($item->variant_display)
                                    <small class="text-muted d-block">{{ $item->variant_display }}</small>
                                @endif
                                <small class="text-muted">{{ number_format($unitP, 0, ',', '.') }}₫ × {{ $item->quantity }}{!! $fi && $fi->remaining > 0 ? ' <span class="badge badge-danger">Flash</span>' : '' !!}</small>
                            </div>
                            <span class="text-danger font-weight-bold">{{ number_format($lineTotal, 0, ',', '.') }}₫</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Tạm tính</span>
                        <span id="subtotal-amount">{{ number_format($subtotal ?? 0, 0, ',', '.') }}₫</span>
                    </div>
                    @if(($discount ?? 0) > 0)
                    <div class="d-flex justify-content-between align-items-center mb-2 text-success">
                        <span>Giảm giá ({{ $cart->coupon?->code }})</span>
                        <span id="discount-amount">−{{ number_format($discount, 0, ',', '.') }}₫</span>
                    </div>
                    @endif
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Sau giảm giá</span>
                        <span id="after-discount-amount">{{ number_format($subtotalAfterDiscount ?? ($subtotal ?? 0), 0, ',', '.') }}₫</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Phí ship</span>
                        <span id="shipping-fee-amount">—</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center font-weight-bold text-danger h5 mb-0">
                        <span>Tổng tiền</span>
                        <span id="total-amount">{{ number_format(($subtotalAfterDiscount ?? ($subtotal ?? 0)), 0, ',', '.') }}₫</span>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-danger btn-block btn-lg">Đặt hàng</button>
            <p class="small text-muted mt-2 text-center">
                <a href="{{ route('addresses.index') }}">Quản lý sổ địa chỉ</a>
            </p>
        </div>
    </div>
</form>

<style>
#address-suggest-dropdown .address-suggest-item:hover { background: #f8f9fa; }
.checkout-shipping-card .form-control {
    border-radius: 1rem;
}
</style>
@push('scripts')
<script>
(function() {
    var subtotal = {{ (int) ($subtotalAfterDiscount ?? $subtotal ?? 0) }};
    var shippingFee = 0;
    var shippingFeeEl = document.getElementById('shipping-fee-amount');
    var totalEl = document.getElementById('total-amount');
    var shippingFeeUrl = '{{ route("checkout.shipping-fee") }}';

    function formatVnd(n) {
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + '₫';
    }
    function updateTotal() {
        if (totalEl) totalEl.textContent = formatVnd(subtotal + shippingFee);
    }
    function fetchShippingFee(lat, lng) {
        var url = shippingFeeUrl;
        if (lat != null && lng != null && lat !== '' && lng !== '') {
            url += '?lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng);
        }
        fetch(url).then(function(r) { return r.json(); }).then(function(data) {
            shippingFee = data.fee || 0;
            if (shippingFeeEl) {
                shippingFeeEl.textContent = formatVnd(shippingFee);
                if (data.distance_km != null) shippingFeeEl.title = 'Khoảng cách: ' + data.distance_km + ' km';
            }
            updateTotal();
        }).catch(function() {
            if (shippingFeeEl) shippingFeeEl.textContent = '—';
            shippingFee = 0;
            updateTotal();
        });
    }

    var addrNew = document.getElementById('addr_new');
    var newFields = document.getElementById('new-address-fields');
    function toggle() {
        var useNew = !addrNew || addrNew.checked;
        if (newFields) newFields.classList.toggle('d-none', !useNew);
        var req = document.querySelectorAll('#full_name, #phone, #address, #lat, #lng');
        req.forEach(function(el) { if (el) el.removeAttribute('required'); });
        if (useNew) {
            var fn = document.getElementById('full_name'), ph = document.getElementById('phone'), ad = document.getElementById('address'), lat = document.getElementById('lat'), lng = document.getElementById('lng');
            if (fn) fn.setAttribute('required', 'required');
            if (ph) ph.setAttribute('required', 'required');
            if (ad) ad.setAttribute('required', 'required');
            if (lat) lat.setAttribute('required', 'required');
            if (lng) lng.setAttribute('required', 'required');
            if (window.refreshLeafletMap) setTimeout(function() { window.refreshLeafletMap('checkout-map'); }, 150);
        }
        updateShippingFromSelection();
    }
    function updateShippingFromSelection() {
        var useNew = !addrNew || addrNew.checked;
        if (useNew) {
            var latInput = document.getElementById('lat'), lngInput = document.getElementById('lng');
            if (latInput && lngInput && latInput.value && lngInput.value) {
                fetchShippingFee(latInput.value, lngInput.value);
            } else {
                shippingFeeEl.textContent = 'Chọn địa chỉ trên bản đồ';
                shippingFee = 0;
                updateTotal();
            }
        } else {
            var checked = document.querySelector('input.address-option:checked');
            if (checked && checked.value) {
                var lat = checked.getAttribute('data-lat'), lng = checked.getAttribute('data-lng');
                if (lat && lng) fetchShippingFee(lat, lng);
                else { shippingFeeEl.textContent = formatVnd(25000); shippingFee = 25000; updateTotal(); }
            } else {
                shippingFeeEl.textContent = '—';
                shippingFee = 0;
                updateTotal();
            }
        }
    }
    document.querySelectorAll('.address-option').forEach(function(r) {
        r.addEventListener('change', function() { toggle(); });
    });
    var latInput = document.getElementById('lat'), lngInput = document.getElementById('lng');
    if (latInput && lngInput) {
        function onLatLngChange() {
            if (addrNew && addrNew.checked && latInput.value && lngInput.value) fetchShippingFee(latInput.value, lngInput.value);
        }
        latInput.addEventListener('change', onLatLngChange);
        lngInput.addEventListener('change', onLatLngChange);
        var observer = new MutationObserver(onLatLngChange);
        observer.observe(latInput, { attributes: true, attributeFilter: ['value'] });
        observer.observe(lngInput, { attributes: true, attributeFilter: ['value'] });
        setInterval(function() {
            if (addrNew && addrNew.checked && latInput.value && lngInput.value && shippingFeeEl.textContent === 'Chọn địa chỉ trên bản đồ') fetchShippingFee(latInput.value, lngInput.value);
        }, 1500);
    }
    toggle();
})();
</script>
@endpush
@endsection
