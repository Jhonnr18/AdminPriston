@php
    $code = strtoupper((string) ($code ?? ''));
    $size = (int) ($size ?? 40);
    $prefix = $code !== '' ? strtolower(substr($code, 0, 2)) : 'xx';
    $label = $label ?? ($code !== '' ? substr($code, 0, 2) : '?');
    $iconUrl = $code !== '' && ! in_array($code, ['GOLD', 'AIR'], true)
        ? url('/icon.php?c='.$code)
        : null;
@endphp
@if ($iconUrl)
    <img
        class="vlh-icon-img"
        src="{{ $iconUrl }}"
        alt="{{ $code }}"
        width="{{ $size }}"
        height="{{ $size }}"
        loading="lazy"
        decoding="async"
        fetchpriority="low"
        style="width:{{ $size }}px;height:{{ $size }}px;object-fit:contain;image-rendering:pixelated;border:1px solid var(--color-vlh-border);border-radius:.25rem;background:#0a0d12;display:block;flex-shrink:0"
        onerror="this.style.display='none';this.nextElementSibling.style.display='grid'"
    >
    <div class="vlh-icon {{ $prefix }}" style="width:{{ $size }}px;height:{{ $size }}px;display:none">{{ $label }}</div>
@else
    <div class="vlh-icon {{ $prefix }}" style="width:{{ $size }}px;height:{{ $size }}px">{{ $label }}</div>
@endif
