@props([
    'title' => null,
    'subtitle' => null,
    'breadcrumb' => null,
    'backUrl' => route('hr.reports.index'),
    'backLabel' => 'Back to Reports',
])

<div class="hr-report-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
    <div>
        <a href="{{ $backUrl }}" class="hr-btn hr-btn-secondary hr-btn-sm" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; text-decoration: none;">
            <i class="ph ph-arrow-left"></i>
            <span>{{ $backLabel }}</span>
        </a>
    </div>
    @if(isset($slot) && trim($slot) !== '')
        <div class="hr-report-header-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            {{ $slot }}
        </div>
    @endif
</div>

