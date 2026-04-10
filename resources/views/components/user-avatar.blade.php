@props([
    'user',
    'size' => 40,
    'class' => 'rounded-circle',
    'extraStyle' => '',
])

@php
    $style = trim("width:{$size}px;height:{$size}px;object-fit:cover;".$extraStyle);
@endphp
@if($user->avatar)
    <img src="{{ '/images/avatars/'.basename($user->avatar) }}" alt="{{ $user->name }}" class="{{ $class }}" style="{{ $style }}">
@else
    <img src="{{ $user->initialsAvatarUrl() }}" alt="{{ $user->name }}" class="{{ $class }}" style="{{ $style }}" width="{{ $size }}" height="{{ $size }}" loading="lazy" decoding="async">
@endif
