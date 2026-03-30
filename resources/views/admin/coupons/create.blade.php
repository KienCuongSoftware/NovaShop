@extends('layouts.admin')

@section('title', 'Tạo mã giảm giá')

@section('content')
<div class="page-header">
    <h2>Tạo mã giảm giá</h2>
    <a class="btn btn-primary" href="{{ route('admin.coupons.index') }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.coupons.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label><strong>Mã (CODE)</strong></label>
                <input type="text" name="code" class="form-control text-uppercase" value="{{ old('code') }}" required maxlength="64" placeholder="VD: SALE10">
            </div>
            <div class="form-group">
                <label>Tên mô tả (tuỳ chọn)</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}">
            </div>
            <div class="row">
                <div class="col-md-4 form-group">
                    <label>Loại</label>
                    <select name="discount_type" class="form-control" required>
                        <option value="percent" {{ old('discount_type') === 'percent' ? 'selected' : '' }}>Phần trăm (%)</option>
                        <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>Số tiền cố định (₫)</option>
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <label>Giá trị</label>
                    <input type="number" name="discount_value" class="form-control" min="1" value="{{ old('discount_value', 10) }}" required>
                    <small class="text-muted">% hoặc VND tùy loại</small>
                </div>
                <div class="col-md-4 form-group">
                    <label>Đơn tối thiểu (₫)</label>
                    <input type="number" name="min_order_amount" class="form-control" min="0" value="{{ old('min_order_amount', 0) }}" required>
                </div>
            </div>
            <div class="form-group">
                <label>Giới hạn danh mục (tuỳ chọn — chỉ tính SP thuộc nhánh DM này)</label>
                <select name="category_id" class="form-control">
                    <option value="">— Toàn shop —</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ (string)old('category_id') === (string)$cat->id ? 'selected' : '' }}>{{ $cat->full_path }}</option>
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
                                <option value="all" {{ old('user_segment', 'all') === 'all' ? 'selected' : '' }}>Tất cả</option>
                                <option value="vip" {{ old('user_segment') === 'vip' ? 'selected' : '' }}>Chỉ khách VIP</option>
                            </select>
                            <small class="text-muted">Kết hợp được với các ô bên dưới (ví dụ VIP + đơn đầu).</small>
                        </div>
                        <div class="col-md-4 form-group mb-2">
                            <label>Đơn hoàn thành tối thiểu</label>
                            <input type="number" name="min_completed_orders" class="form-control" min="0" value="{{ old('min_completed_orders') }}" placeholder="VD: 5">
                            <small class="text-muted">Để trống = không yêu cầu</small>
                        </div>
                        <div class="col-md-4 form-group mb-2 d-flex align-items-end">
                            <div class="custom-control custom-checkbox">
                                <input type="hidden" name="first_order_only" value="0">
                                <input type="checkbox" name="first_order_only" value="1" class="custom-control-input" id="first_order_only" {{ old('first_order_only') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="first_order_only">Người mua lần đầu (chưa có đơn nào)</label>
                            </div>
                        </div>
                    </div>
                    <div class="row align-items-end">
                        <div class="col-md-4 form-group mb-0">
                            <div class="custom-control custom-checkbox">
                                <input type="hidden" name="birthday_only" value="0">
                                <input type="checkbox" name="birthday_only" value="1" class="custom-control-input" id="birthday_only" {{ old('birthday_only') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="birthday_only">Chỉ trong khoảng sinh nhật</label>
                            </div>
                            <small class="text-muted">Cần ngày sinh trên tài khoản khách.</small>
                        </div>
                        <div class="col-md-4 form-group mb-0">
                            <label>± ngày quanh sinh nhật</label>
                            <input type="number" name="birthday_window_days" class="form-control" min="0" max="60" value="{{ old('birthday_window_days', 7) }}">
                            <small class="text-muted">0 = đúng ngày; 7 = tuần lễ sinh nhật.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Bắt đầu (tuỳ chọn)</label>
                    <input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at') }}">
                </div>
                <div class="col-md-6 form-group">
                    <label>Kết thúc (tuỳ chọn)</label>
                    <input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at') }}">
                </div>
            </div>
            <div class="form-group">
                <label>Số lần dùng tối đa (để trống = không giới hạn)</label>
                <input type="number" name="max_uses" class="form-control" min="1" value="{{ old('max_uses') }}">
            </div>
            <div class="form-check mb-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Đang kích hoạt</label>
            </div>
            <button type="submit" class="btn btn-primary">Lưu</button>
        </form>
    </div>
</div>
@endsection
