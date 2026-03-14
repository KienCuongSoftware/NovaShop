@php
    $activeCategoryIds = $activeCategoryIds ?? [];
    $isActive = in_array($category->id, $activeCategoryIds);
    $url = isset($q) ? route('search', array_filter(['q' => $q ?? '', 'category_id' => $category->id])) : route('category.products', $category);
@endphp
<a href="{{ $url }}" class="{{ $isActive ? 'active' : '' }}" style="padding-left: {{ ($level + 1) * 12 }}px;">
    {{ $category->name }}
</a>
@foreach($category->children ?? [] as $child)
    @include('partials.category-tree-item', ['category' => $child, 'level' => $level + 1])
@endforeach
