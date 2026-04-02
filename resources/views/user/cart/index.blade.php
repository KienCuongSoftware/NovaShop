@extends('layouts.user')

@section('title', 'Giỏ hàng - NovaShop')

@section('content')
<div class="page-header mb-4">
    <h2>Giỏ hàng</h2>
</div>

@if($cart->items->isEmpty())
<div class="card">
    <div class="card-body text-center py-5">
        <p class="text-muted mb-3">Giỏ hàng trống.</p>
        <div class="d-flex justify-content-center gap-2 flex-wrap">
            <a href="{{ route('welcome') }}" class="btn btn-primary">Mua sắm ngay</a>
            <a href="{{ route('orders.index') }}" class="btn btn-outline-primary">Xem đơn hàng</a>
        </div>
    </div>
</div>
@else
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 80px;">Ảnh</th>
                        <th>Sản phẩm</th>
                        <th class="text-right">Đơn giá</th>
                        <th class="text-center" style="width: 140px;">Số lượng</th>
                        <th class="text-right">Thành tiền</th>
                        <th style="width: 60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cart->items as $item)
                    @php
                        $flashItem = ($activeFlashSale ?? null) && $item->product_variant_id
                            ? $activeFlashSale->items->firstWhere('product_variant_id', $item->product_variant_id)
                            : null;
                        $unitPrice = $flashItem && $flashItem->remaining > 0
                            ? (float) $flashItem->sale_price
                            : ($item->productVariant ? (float) $item->productVariant->price : (float) $item->product->price);
                        $maxQty = $item->productVariant ? $item->productVariant->stock : (int) $item->product->quantity;
                        if ($flashItem) {
                            $maxQty = min($maxQty, $flashItem->remaining);
                        }
                        $subtotal = $unitPrice * $item->quantity;
                    @endphp
                    <tr>
                        <td>
                            @if($item->product->image)
                                <img src="/images/products/{{ basename($item->product->image) }}" alt="{{ $item->product->name }}" class="img-fluid rounded" style="max-height: 60px; object-fit: contain;">
                            @else
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 0.75rem; color: #999;">N/A</div>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('products.show', $item->product) }}" class="font-weight-bold text-dark">{{ $item->product->name }}</a>
                            @if($item->variant_display)
                                <br><small class="text-muted">{{ $item->variant_display }}</small>
                            @endif
                        </td>
                        <td class="text-right">
                            @if($flashItem && $flashItem->remaining > 0)
                                <span class="text-danger font-weight-bold">{{ number_format($unitPrice, 0, ',', '.') }}₫</span>
                                <br><small class="text-muted">Flash Sale</small>
                            @else
                                {{ number_format($unitPrice, 0, ',', '.') }}₫
                            @endif
                        </td>
                        <td class="text-center">
                            <input type="number"
                                name="quantity"
                                value="{{ $item->quantity }}"
                                min="1"
                                max="{{ $maxQty }}"
                                class="form-control form-control-sm text-center d-inline-block cart-qty-input"
                                style="width: 70px;"
                                data-cart-item-id="{{ $item->id }}"
                                data-max-qty="{{ $maxQty }}"
                                aria-label="Số lượng {{ $item->product->name }}">
                        </td>
                        <td class="text-right font-weight-bold text-danger">
                            <span class="cart-line-total" data-cart-item-id="{{ $item->id }}">{{ number_format($subtotal, 0, ',', '.') }}₫</span>
                        </td>
                        <td>
                            <form action="{{ route('cart.remove', $item) }}" method="POST" class="d-inline cart-remove-form" id="cart-remove-{{ $item->id }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-outline-danger cart-remove-btn" title="Xóa" data-form-id="cart-remove-{{ $item->id }}">&times;</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <div class="mb-3">
            <div class="d-flex flex-wrap align-items-center">
                <form action="{{ route('cart.coupon.apply') }}" method="POST" class="form-inline mb-2">
                    @csrf
                    <label class="sr-only" for="coupon-code">Mã giảm giá</label>
                    <input type="text" name="code" id="coupon-code" class="form-control mr-2" placeholder="Mã giảm giá / voucher" value="{{ old('code', $cart->coupon?->code) }}" style="min-width: 180px;">
                    <button type="submit" class="btn btn-outline-danger">Áp dụng</button>
                </form>
                @if($cart->coupon_id)
                <form action="{{ route('cart.coupon.remove') }}" method="POST" class="mb-2 ml-2">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-link p-0">Bỏ mã</button>
                </form>
                @endif
            </div>
            <div id="cart-coupon-message" class="small text-danger {{ empty($couponError) ? 'd-none' : '' }}">{{ $couponError }}</div>
        </div>
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <div class="small text-muted">Tạm tính: <span id="cart-subtotal-amount">{{ number_format($cartSubtotal ?? 0, 0, ',', '.') }}₫</span></div>
                <div id="cart-discount-line" class="small text-success {{ ($couponDiscount ?? 0) > 0 ? '' : 'd-none' }}">Giảm giá: −<span id="cart-discount-amount">{{ number_format($couponDiscount ?? 0, 0, ',', '.') }}₫</span></div>
                <span class="font-weight-bold">Tổng cộng: <span class="text-danger" id="cart-total-amount">{{ number_format($totalAfterCoupon ?? ($cartSubtotal ?? 0), 0, ',', '.') }}₫</span></span>
            </div>
            <div class="d-flex mt-2 mt-md-0">
                <a href="{{ route('welcome') }}" class="btn btn-outline-secondary mr-2">Tiếp tục mua sắm</a>
                <a href="{{ route('checkout.show') }}" class="btn btn-danger">Mua hàng</a>
            </div>
        </div>
    </div>
</div>

{{-- Modal xác nhận xóa khỏi giỏ --}}
<div class="modal fade" id="cartRemoveModal" tabindex="-1" aria-labelledby="cartRemoveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="cartRemoveModalLabel">Xác nhận</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body pt-0">
                Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="cartRemoveConfirmBtn">Xóa</button>
            </div>
        </div>
    </div>
</div>
<script>
window.addEventListener('load', function() {
    var cartUpdateUrl = @json(route('cart.update'));
    var csrfToken = @json(csrf_token());

    function formatVnd(n) {
        return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + '₫';
    }

    function updateNavbarCartBadge(sum) {
        var cartLink = document.querySelector('a.navbar-cart-link[title="Giỏ hàng"]');
        if (!cartLink) return;
        var badge = cartLink.querySelector('.navbar-cart-badge');
        if (sum > 0) {
            var t = sum > 99 ? '99+' : String(sum);
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'navbar-cart-badge';
                cartLink.appendChild(badge);
            }
            badge.textContent = t;
        } else if (badge) {
            badge.remove();
        }
    }

    document.querySelectorAll('.cart-qty-input').forEach(function(input) {
        var busy = false;
        var lastGood = String(input.value);

        function applyTotals(data) {
            var lineEl = document.querySelector('.cart-line-total[data-cart-item-id="' + data.item.id + '"]');
            if (lineEl) lineEl.textContent = formatVnd(data.item.line_total);
            var subEl = document.getElementById('cart-subtotal-amount');
            var totEl = document.getElementById('cart-total-amount');
            var discLine = document.getElementById('cart-discount-line');
            var discAmt = document.getElementById('cart-discount-amount');
            var msgEl = document.getElementById('cart-coupon-message');
            if (subEl) subEl.textContent = formatVnd(data.cart_subtotal);
            if (totEl) totEl.textContent = formatVnd(data.total_after_coupon);
            if (discLine && discAmt) {
                if (data.coupon_discount > 0) {
                    discLine.classList.remove('d-none');
                    discAmt.textContent = formatVnd(data.coupon_discount);
                } else {
                    discLine.classList.add('d-none');
                }
            }
            if (msgEl) {
                if (data.coupon_error) {
                    msgEl.textContent = data.coupon_error;
                    msgEl.classList.remove('d-none');
                } else {
                    msgEl.textContent = '';
                    msgEl.classList.add('d-none');
                }
            }
            updateNavbarCartBadge(data.cart_quantity_sum);
        }

        function submitQty() {
            var qty = parseInt(input.value, 10);
            var maxQ = parseInt(input.getAttribute('max') || input.dataset.maxQty || '9999', 10);
            if (isNaN(qty) || qty < 1) {
                input.value = lastGood;
                return;
            }
            if (qty > maxQ) {
                input.value = String(maxQ);
                qty = maxQ;
            }
            if (String(qty) === lastGood && !busy) return;
            if (busy) return;
            busy = true;
            input.disabled = true;

            fetch(cartUpdateUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    cart_item_id: parseInt(input.dataset.cartItemId, 10),
                    quantity: qty
                })
            }).then(function(r) {
                return r.json().catch(function() { return {}; }).then(function(data) {
                    return { ok: r.ok, status: r.status, data: data };
                });
            }).then(function(res) {
                busy = false;
                input.disabled = false;
                if (res.ok && res.data && res.data.ok) {
                    lastGood = String(res.data.item.quantity);
                    input.value = lastGood;
                    applyTotals(res.data);
                } else {
                    var d = res.data || {};
                    var msg = d.message || 'Không cập nhật được giỏ hàng.';
                    if (d.errors && typeof d.errors === 'object') {
                        var first = Object.keys(d.errors)[0];
                        if (first && d.errors[first] && d.errors[first][0]) msg = d.errors[first][0];
                    }
                    if (d.max_quantity != null) {
                        input.setAttribute('max', d.max_quantity);
                        input.dataset.maxQty = String(d.max_quantity);
                    }
                    input.value = lastGood;
                    if (typeof window.alert === 'function') window.alert(msg);
                }
            }).catch(function() {
                busy = false;
                input.disabled = false;
                input.value = lastGood;
                if (typeof window.alert === 'function') window.alert('Lỗi mạng. Thử lại sau.');
            });
        }

        input.addEventListener('change', submitQty);
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                input.blur();
            }
        });
    });

    var formToSubmit = null;
    document.querySelectorAll('.cart-remove-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-form-id');
            formToSubmit = document.getElementById(id);
            if (!formToSubmit) return;
            if (typeof $ !== 'undefined' && $.fn.modal) {
                $('#cartRemoveModal').modal('show');
            } else if (typeof window.bsConfirm === 'function') {
                window.bsConfirm('Xóa sản phẩm này khỏi giỏ hàng?').then(function(ok) {
                    if (ok) formToSubmit.submit();
                });
            }
        });
    });
    var confirmBtn = document.getElementById('cartRemoveConfirmBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            if (formToSubmit) formToSubmit.submit();
            if (typeof $ !== 'undefined' && $.fn.modal) $('#cartRemoveModal').modal('hide');
        });
    }
});
</script>
@endif
@endsection
