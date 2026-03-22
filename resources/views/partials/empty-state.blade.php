{{-- $title, $message, $type: wishlist|compare|bell --}}
@php
    $type = $type ?? 'wishlist';
@endphp
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 px-4">
        <div class="mb-3 text-muted d-flex justify-content-center" style="opacity: .85;" aria-hidden="true">
            @if($type === 'compare')
                <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="7" height="14" rx="1"/><rect x="14" y="5" width="7" height="14" rx="1"/><path d="M10 9h4M10 15h4"/></svg>
            @elseif($type === 'bell')
                <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            @endif
        </div>
        <h3 class="h5 font-weight-bold text-dark mb-2">{{ $title ?? 'Trống' }}</h3>
        <p class="text-muted mb-4 mb-md-0" style="max-width: 420px; margin-left: auto; margin-right: auto;">{{ $message ?? '' }}</p>
        @isset($actionUrl)
            <a href="{{ $actionUrl }}" class="btn btn-danger mt-3">{{ $actionLabel ?? 'Khám phá' }}</a>
        @endisset
    </div>
</div>
