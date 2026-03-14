@extends('layouts.user')

@section('title', 'Tất cả danh mục - NovaShop')

@section('content')
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb bg-transparent p-0 mb-0">
        <li class="breadcrumb-item"><a href="{{ url('/') }}">Trang chủ</a></li>
        <li class="breadcrumb-item active" aria-current="page">Tất cả danh mục</li>
    </ol>
</nav>

<h1 class="mb-4 font-weight-bold" style="font-size: 1.5rem; color: #212529;">Tất cả danh mục</h1>

@if($categories->isNotEmpty())
<div class="all-categories-hierarchy">
    @foreach($categories as $root)
    <div class="category-group mb-4">
        <h3 class="category-group-title mb-3">
            <a href="{{ route('category.products', $root) }}" class="text-dark font-weight-bold">{{ $root->name }}</a>
        </h3>
        @if($root->children->isNotEmpty())
        <div class="category-group-children">
            @foreach($root->children as $child)
            <div class="category-subgroup mb-2">
                <a href="{{ route('category.products', $child) }}" class="text-danger font-weight-medium d-block mb-1">{{ $child->name }}</a>
                @if($child->children->isNotEmpty())
                <div class="category-leaves ml-3">
                    @foreach($child->children as $leaf)
                    <a href="{{ route('category.products', $leaf) }}" class="d-block text-muted small mb-1">{{ $leaf->name }}</a>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endforeach
</div>
@else
<p class="text-muted">Chưa có danh mục nào.</p>
@endif
@endsection
