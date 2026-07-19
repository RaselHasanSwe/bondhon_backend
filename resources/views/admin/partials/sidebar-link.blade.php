@php
    $href     = route($route);
    $isActive = $active ?? request()->routeIs($routePattern ?? $route);
    $count    = (int) ($badge ?? 0);
@endphp
<a href="{{ $href }}"
   class="nav-link {{ $isActive ? 'active' : '' }}"
   @if(!empty($style)) style="{{ $style }}" @endif>
    <i class="bi {{ $icon }}"></i> {{ $label }}
    @if($count > 0)
        <span class="badge {{ $badgeClass ?? 'bg-danger' }} ms-auto"
              @if(!empty($badgeStyle)) style="{{ $badgeStyle }}" @endif>
            @if(!empty($badgeCap) && $count > $badgeCap){{ $badgeCap }}+@else{{ $count }}@endif
        </span>
    @endif
</a>
