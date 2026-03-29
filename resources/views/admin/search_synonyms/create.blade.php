@extends('layouts.admin')

@section('title', 'Thêm synonym')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <h2 class="mb-0">Thêm synonym</h2>
    <a class="btn btn-primary" href="{{ route('admin.search-synonyms.index') }}">Quay lại</a>
</div>

<div class="card mt-3">
    <div class="card-body">
        <form action="{{ route('admin.search-synonyms.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label><strong>Keyword</strong></label>
                <input type="text" name="keyword" class="form-control @error('keyword') is-invalid @enderror" value="{{ old('keyword') }}" required maxlength="255" placeholder="VD: iphone">
                @error('keyword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted">Sẽ được normalize về chữ thường khi lưu.</small>
            </div>

            <div class="form-group">
                <label><strong>Synonym</strong></label>
                <input type="text" name="synonym" class="form-control @error('synonym') is-invalid @enderror" value="{{ old('synonym') }}" required maxlength="255" placeholder="VD: điện thoại apple">
                @error('synonym')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn btn-success">Lưu</button>
        </form>
    </div>
</div>
@endsection

