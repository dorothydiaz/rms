@extends('layouts.app')

@section('title', 'Overtime Records - Attendance Management')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-trend-up"></i>
            Restaurant Overtime Hours & Approvals
        </h1>
        <p class="hr-page-subtitle">Track overtime hours rendered beyond scheduled shift templates</p>
    </div>
</div>

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Staff Name</th>
                    <th>Branch & Dept</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Total Hours</th>
                    <th>Overtime Rendered</th>
                    <th style="text-align: right;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                    <tr>
                        <td><strong>{{ \Carbon\Carbon::parse($rec->date)->format('M d, Y') }}</strong></td>
                        <td>
                            <strong>{{ $rec->employee?->full_name }}</strong><br>
                            <small style="color: #64748b;">{{ $rec->employee?->position?->name }}</small>
                        </td>
                        <td>
                            <div>{{ $rec->employee?->branch?->name }}</div>
                            <small style="color: #64748b;">{{ $rec->employee?->department?->name }}</small>
                        </td>
                        <td>{{ substr($rec->time_in, 0, 5) }}</td>
                        <td>{{ substr($rec->time_out, 0, 5) }}</td>
                        <td>{{ number_format($rec->total_hours, 2) }} hrs</td>
                        <td>
                            <strong style="color: #6366f1; font-size: 14px;">+{{ number_format($rec->overtime_hours, 2) }} hrs</strong>
                        </td>
                        <td style="text-align: right;">
                            <span class="hr-badge hr-badge-success">Approved / Recorded</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align: center; color: #94a3b8; padding: 30px;">No overtime hours logged.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($records->hasPages())
        <div style="padding: 14px 20px; border-top: 1px solid #e2e8f0;">
            {{ $records->links() }}
        </div>
    @endif
</div>
@endsection
