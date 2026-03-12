@extends('layouts.admin')

@section('title', 'Chi tiết danh mục')

@section('content')
<div class="page-header">
    <h2>Chi tiết danh mục</h2>
    <a class="btn btn-primary" href="{{ route('admin.categories.index') }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Tên:</dt>
            <dd class="col-sm-9">{{ $category->name }}</dd>
            @if($category->image)
            <dt class="col-sm-3">Ảnh:</dt>
            <dd class="col-sm-9">
                <img src="/images/categories/{{ basename($category->image) }}" alt="{{ $category->name }}" class="img-thumbnail" style="max-height: 120px;">
            </dd>
            @endif
        </dl>
    </div>
</div>
@endsection
