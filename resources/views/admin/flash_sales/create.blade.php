@extends('layouts.admin')

@section('title', 'Tạo Flash Sale')

@section('content')
<div class="page-header">
    <h2>Tạo chương trình Flash Sale</h2>
    <a href="{{ route('admin.flash-sales.index') }}" class="btn btn-outline-secondary">← Danh sách</a>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.flash-sales.store') }}" method="POST">
    @csrf
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="form-group">
                <label for="name">Tên chương trình <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="start_time">Thời gian bắt đầu <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="start_time" id="start_time" class="form-control" value="{{ old('start_time') }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="end_time">Thời gian kết thúc <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="end_time" id="end_time" class="form-control" value="{{ old('end_time') }}" required>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="status">Trạng thái</label>
                <select name="status" id="status" class="form-control">
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Đang diễn ra</option>
                    <option value="scheduled" {{ old('status') === 'scheduled' ? 'selected' : '' }}>Sắp diễn ra</option>
                    <option value="ended" {{ old('status') === 'ended' ? 'selected' : '' }}>Đã kết thúc</option>
                </select>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Tạo chương trình</button>
            <a href="{{ route('admin.flash-sales.index') }}" class="btn btn-secondary">Hủy</a>
        </div>
    </div>
</form>
@endsection
