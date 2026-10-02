@props([
    'title' => null,
    'icon' => 'ph-chart-polar',
    'subtitle' => null,
    'description' => null,
    'breadcrumb' => null,
    'backUrl' => route('hr.reports.index'),
    'backLabel' => 'Back to Reports',
])

<div class="hr-page-header hr-report-page-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 22px; flex-wrap: wrap; gap: 16px;">
    @if($title)
        <div style="display: flex; align-items: flex-start; gap: 14px; min-width: 0; max-width: 760px;">
            <div class="hr-page-title-icon" style="width: 42px; height: 42px; display: inline-flex; align-items: center; justify-content: center; border-radius: 11px; background: linear-gradient(135deg, rgba(236, 72, 153, 0.14), rgba(168, 85, 247, 0.20)); border: 1px solid rgba(168, 85, 247, 0.32); color: #9333ea; font-size: 22px; box-shadow: 0 4px 14px rgba(168, 85, 247, 0.12), inset 0 1px 1px rgba(255, 255, 255, 0.8); flex-shrink: 0; margin-top: 2px;">
                <i class="ph {{ $icon }}"></i>
            </div>
            <div>
                <h1 class="hr-page-title" style="margin: 0; font-size: 22px; font-weight: 700; color: #0f172a; line-height: 1.25; letter-spacing: -0.02em;">
                    {{ $title }}
                </h1>
                @if($subtitle)
                    <div style="font-size: 13px; font-weight: 500; color: #64748b; margin-top: 3px;">
                        {{ $subtitle }}
                    </div>
                @endif
                @if($description)
                    <p style="font-size: 12.5px; color: #64748b; margin: 4px 0 0 0; line-height: 1.45;">
                        {{ $description }}
                    </p>
                @endif
            </div>
        </div>
    @else
        <div></div>
    @endif

    <div class="hr-page-actions hr-report-header-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-left: auto;">
        <a href="{{ $backUrl }}" class="hr-btn hr-btn-secondary hr-btn-sm" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; text-decoration: none;">
            <i class="ph ph-arrow-left"></i>
            <span>{{ $backLabel }}</span>
        </a>
        @if(isset($slot) && trim($slot) !== '')
            {{ $slot }}
        @endif
    </div>
</div>
