@php
    $isSummit = $node->isTopLevelLeader();
    $deptId = (int) ($node->department_id ?? 0);
    $isDeptHead = isset($deptHeadsMap[$deptId]) && (int)$deptHeadsMap[$deptId] === (int)$node->id;
    $subCount = $node->subordinates->count();
    $deptName = $node->department?->name ?? 'General';
    $deptSlug = strtolower($deptName);
    $companyName = $node->company_or_agency ?: 'Company';
    $companySlug = strtolower($companyName);
    
    $deptPillClass = 'dept-other';
    if (str_contains($deptSlug, 'management')) $deptPillClass = 'dept-mgmt';
    elseif (str_contains($deptSlug, 'front')) $deptPillClass = 'dept-foh';
    elseif (str_contains($deptSlug, 'back')) $deptPillClass = 'dept-boh';
    elseif (str_contains($deptSlug, 'finance') || str_contains($deptSlug, 'admin')) $deptPillClass = 'dept-fin';
@endphp

<li>
    <div class="org-node-card {{ $isSummit ? 'is-summit' : '' }} {{ $isDeptHead ? 'is-dept-head' : '' }}"
         id="org-node-{{ $node->id }}"
         data-id="{{ $node->id }}"
         data-name="{{ $node->full_name }}"
         data-position="{{ $node->position?->name ?? 'Staff' }}"
         data-department="{{ $deptSlug }}"
         data-branch="{{ strtolower($node->branch?->name ?? '') }}"
         data-company="{{ $companySlug }}">

        <!-- Top Header Tags -->
        <div class="org-card-header">
            @if($isSummit)
                <span class="org-badge-role summit"><i class="ph ph-crown"></i> Summit Apex</span>
            @elseif($isDeptHead)
                <span class="org-badge-role head"><i class="ph ph-buildings"></i> Dept Head</span>
            @else
                <span style="font-size: 11px; font-weight: 600; color: #94a3b8; font-family: monospace;">{{ $node->employee_code ?? "EMP-{$node->id}" }}</span>
            @endif

            <div class="org-card-actions">
                <button type="button" class="org-card-menu-btn" title="Actions" onclick="openReassignManagerModal({{ $node->id }}, '{{ addslashes($node->full_name) }}', {{ $node->supervisor_id ?? 'null' }})">
                    <i class="ph ph-user-switch"></i>
                </button>
            </div>
        </div>

        <!-- Profile Info Row -->
        <div class="org-card-profile">
            @if($node->photo_url)
                <img src="{{ $node->photo_url }}" class="org-card-avatar" alt="{{ $node->full_name }}">
            @else
                <div class="org-card-initials">{{ $node->initials }}</div>
            @endif
            <div class="org-card-name-group">
                <div class="org-card-name">
                    <a href="{{ route('hr.people.employees.show', $node->id) }}" style="color: inherit; text-decoration: none;" title="View 201 File">
                        {{ $node->full_name }}
                    </a>
                </div>
                <div class="org-card-title">{{ $node->position?->name ?? 'Staff' }}</div>
            </div>
        </div>

        <!-- Meta Pills -->
        <div class="org-card-meta">
            <span class="org-pill {{ $deptPillClass }}" title="Department">
                <i class="ph ph-tree-structure"></i> {{ $deptName }}
            </span>
            <span class="org-pill branch" title="Branch Location">
                <i class="ph ph-map-pin"></i> {{ $node->branch?->name ?? 'Main Branch' }}
            </span>
            <span class="org-pill company" title="Company / Agency">
                <i class="ph ph-briefcase"></i> {{ $companyName }}
            </span>
        </div>

        <!-- Footer / Subordinates Count Toggle -->
        <div class="org-card-footer">
            @if($subCount > 0)
                <button type="button" class="org-collapse-btn" data-count="{{ $subCount }} {{ Str::plural('report', $subCount) }}" title="Click to collapse/expand team">
                    <i class="ph ph-minus-circle"></i>
                    <span>{{ $subCount }} {{ Str::plural('report', $subCount) }}</span>
                </button>
            @else
                <span style="color: #94a3b8; font-size: 10.5px; font-style: italic;">Individual Contributor</span>
            @endif

            <a href="{{ route('hr.people.employees.show', $node->id) }}" style="color: #7c3aed; font-size: 11px; font-weight: 600; text-decoration: none;" title="Open Profile">
                201 File &rarr;
            </a>
        </div>
    </div>

    <!-- Recursive Subordinates -->
    @if($node->subordinates->isNotEmpty())
        <ul class="org-sub-tree">
            @foreach($node->subordinates as $child)
                @include('hr.people.partials.org-tree-node', ['node' => $child, 'deptHeadsMap' => $deptHeadsMap])
            @endforeach
        </ul>
    @endif
</li>
