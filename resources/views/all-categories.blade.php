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
<div class="all-categories-grid">
    @foreach($categories as $cat)
    <a href="{{ route('category.products', $cat) }}" class="all-categories-item">
        <span class="all-categories-icon">
            @if($cat->image)
                <img src="/images/categories/{{ basename($cat->image) }}" alt="{{ $cat->name }}" loading="lazy">
            @else
                <span class="all-categories-icon-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                </span>
            @endif
        </span>
        <span class="all-categories-name">{{ $cat->name }}</span>
    </a>
    @endforeach
</div>
@else
<p class="text-muted">Chưa có danh mục nào.</p>
@endif
@endsection
