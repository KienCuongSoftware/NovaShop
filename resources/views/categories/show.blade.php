<!-- resources/views/categories/show.blade.php -->
@extends('layouts.app')

@section('title', 'Chi tiết danh mục')

@section('content')
<div class="page-header">
    <h2>Chi tiết danh mục</h2>
    <a class="btn btn-primary" href="{{ route('categories.index') }}">Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Tên:</dt>
            <dd class="col-sm-9">{{ $category->name }}</dd>
        </dl>
    </div>
</div>
@endsection
