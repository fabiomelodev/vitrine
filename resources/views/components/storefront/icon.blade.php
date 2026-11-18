@props(['name', 'size' => 20])
@php
    $paths = [
        'menu' => '<path d="M4 7h16M4 12h16M4 17h10"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
        'heart' => '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/>',
        'bag' => '<path d="M5 8h14l-1 12H6L5 8zM9 8V6a3 3 0 0 1 6 0v2"/>',
        'whatsapp' => '<path d="M4 20l1.3-3.9A8 8 0 1 1 8 19l-4 1z"/>',
        'close' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'chevron-left' => '<path d="M15 6l-6 6 6 6"/>',
        'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
        'arrow-up-right' => '<path d="M7 17L17 7M9 7h8v8"/>',
        'check' => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        'copy' => '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3"/>',
        'trash' => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
        'info' => '<circle cx="12" cy="12" r="8"/><path d="M12 11v5M12 8h.01"/>',
        'filter' => '<path d="M4 6h16M7 12h10M10 18h4"/>',
        'clock' => '<circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
    ];
@endphp
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes }}>{!! $paths[$name] ?? '' !!}</svg>
