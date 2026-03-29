@extends('layouts.admin')

@section('title', 'Sửa synonym')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <h2 class="mb-0">Sửa synonym</h2>
    <a class="btn btn-primary" href="{{ route('admin.search-synonyms.index') }}">Quay lại</a>
</div>

<div class="card mt-3">
    <div class="card-body">
        <form action="{{ route('admin.search-synonyms.update', $row) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label><strong>Keyword</strong></label>
                <input type="text" name="keyword" class="form-control @error('keyword') is-invalid @enderror" value="{{ old('keyword', $row->keyword) }}" required maxlength="255">
                @error('keyword')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label><strong>Synonym</strong></label>
                <input type="text" name="synonym" class="form-control @error('synonym') is-invalid @enderror" value="{{ old('synonym', $row->synonym) }}" required maxlength="255">
                @error('synonym')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn btn-success">Cập nhật</button>
        </form>
    </div>
</div>
@endsection

