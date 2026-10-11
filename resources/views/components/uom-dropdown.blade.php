@props([
    'name' => 'uom_code',
    'id' => null,
    'value' => null,
    'selected' => null,
    'category' => null,
    'placeholder' => 'Select Unit of Measure',
    'required' => false,
    'disabled' => false,
    'class' => '',
])

@php
    $inputId = $id ?? 'uom_' . Str::slug($name, '_') . '_' . Str::random(4);
    $currentVal = $value ?? $selected ?? '';
@endphp

<div class="uom-dropdown-wrapper {{ $class }}" 
     data-uom-dropdown 
     data-uom-target="#{{ $inputId }}"
     data-uom-category="{{ $category ?? 'ALL' }}"
     data-uom-placeholder="{{ $placeholder }}">
    
    {{-- Native Hidden Select / Fallback for Form Submission & Automated Testing --}}
    <select name="{{ $name }}" 
            id="{{ $inputId }}" 
            class="uom-native-select"
            data-uom-select
            data-uom-current="{{ $currentVal }}"
            data-uom-category="{{ $category ?? '' }}"
            @if($required) required @endif
            @if($disabled) disabled @endif>
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @if($currentVal)
            <option value="{{ $currentVal }}" selected>{{ $currentVal }}</option>
        @endif
    </select>
</div>
