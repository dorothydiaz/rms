@props([
    'class' => 'hr-filter-bar',
    'id' => null,
])

<div class="filter-bar-wrapper">
    <button type="button" class="filter-bar-scroll-btn filter-scroll-left is-hidden" aria-label="Scroll filter bar left">
        <i class="ph ph-caret-left"></i>
    </button>
    <div {{ $attributes->merge(['class' => 'filter-bar-scroll-track ' . $class]) }} @if($id) id="{{ $id }}" @endif data-filter-bar="true">
        {{ $slot }}
    </div>
    <button type="button" class="filter-bar-scroll-btn filter-scroll-right is-hidden" aria-label="Scroll filter bar right">
        <i class="ph ph-caret-right"></i>
    </button>
</div>
