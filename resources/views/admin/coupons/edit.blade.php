@extends('layouts.admin')

@section('title', 'Sửa mã giảm giá')

@section('content')
<div class="page-header">
    <h2>Sửa mã: {{ $coupon->code }}</h2>
    <a class="btn btn-primary" href="{{ route('admin.coupons.index') }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.coupons.update', $coupon) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label><strong>Mã (CODE)</strong></label>
                <input type="text" name="code" class="form-control text-uppercase" value="{{ old('code', $coupon->code) }}" required maxlength="64">
            </div>
            <div class="form-group">
                <label>Tên mô tả</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $coupon->name) }}">
            </div>
            <div class="row">
                <div class="col-md-4 form-group">
                    <label>Loại</label>
                    <select name="discount_type" class="form-control" required>
                        <option value="percent" {{ old('discount_type', $coupon->discount_type) === 'percent' ? 'selected' : '' }}>Phần trăm (%)</option>
                        <option value="fixed" {{ old('discount_type', $coupon->discount_type) === 'fixed' ? 'selected' : '' }}>Số tiền cố định (₫)</option>
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <label>Giá trị</label>
                    <input type="number" name="discount_value" class="form-control" min="1" value="{{ old('discount_value', $coupon->discount_value) }}" required>
                </div>
                <div class="col-md-4 form-group">
                    <label>Đơn tối thiểu (₫)</label>
                    <input type="number" name="min_order_amount" class="form-control" min="0" value="{{ old('min_order_amount', $coupon->min_order_amount) }}" required>
                </div>
            </div>
            <div class="form-group">
                <label>Giới hạn danh mục</label>
                <select name="category_id" class="form-control">
                    <option value="">— Toàn shop —</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ (string)old('category_id', $coupon->category_id) === (string)$cat->id ? 'selected' : '' }}>{{ $cat->full_path }}</option>
                    @endforeach
                </select>
            </div>
            <div class="card mb-3" style="background:#fafbfc;">
                <div class="card-body py-3">
                    <div class="font-weight-bold mb-2">Điều kiện người dùng</div>
                    <div class="row">
                        <div class="col-md-4 form-group mb-2">
                            <label>Segment</label>
                            <select name="user_segment" class="form-control">
                                <option value="all" {{ old('user_segment', $coupon->user_segment ?? 'all') === 'all' ? 'selected' : '' }}>Tất cả</option>
                                <option value="vip" {{ old('user_segment', $coupon->user_segment ?? 'all') === 'vip' ? 'selected' : '' }}>VIP</option>
                            </select>
                        </div>
                        <div class="col-md-4 form-group mb-2">
                            <label>Đơn hoàn thành tối thiểu</label>
                            <input type="number" name="min_completed_orders" class="form-control" min="0" value="{{ old('min_completed_orders', $coupon->min_completed_orders) }}" placeholder="VD: 5">
                            <small class="text-muted">Để trống = không yêu cầu</small>
                        </div>
                        <div class="col-md-4 form-group mb-2 d-flex align-items-end">
                            <div class="custom-control custom-checkbox">
                                <input type="hidden" name="first_order_only" value="0">
                                <input type="checkbox" name="first_order_only" value="1" class="custom-control-input" id="first_order_only" {{ old('first_order_only', $coupon->first_order_only) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="first_order_only">Chỉ đơn đầu tiên</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Bắt đầu</label>
                    @php
                        $s = old('starts_at', $coupon->starts_at ? $coupon->starts_at->format('Y-m-d\TH:i') : '');
                        $e = old('ends_at', $coupon->ends_at ? $coupon->ends_at->format('Y-m-d\TH:i') : '');
                    @endphp
                    <input type="datetime-local" name="starts_at" class="form-control" value="{{ $s }}">
                </div>
                <div class="col-md-6 form-group">
                    <label>Kết thúc</label>
                    <input type="datetime-local" name="ends_at" class="form-control" value="{{ $e }}">
                </div>
            </div>
            <div class="form-group">
                <label>Số lần dùng tối đa</label>
                <input type="number" name="max_uses" class="form-control" min="1" value="{{ old('max_uses', $coupon->max_uses) }}">
                <small class="text-muted">Đã dùng: {{ $coupon->uses_count }}</small>
            </div>
            <div class="form-check mb-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $coupon->is_active) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Đang kích hoạt</label>
            </div>
            <button type="submit" class="btn btn-primary">Cập nhật</button>
        </form>
    </div>
</div>
@endsection
