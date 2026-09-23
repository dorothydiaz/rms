@extends('layouts.app')

@section('title', 'Employee Documents - People Administration')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-folder-notch-open"></i>
            Employee Documents Repository
        </h1>
        <p class="hr-page-subtitle">Health certificates, food handler permits, contracts, IDs, and clearances</p>
    </div>
    <div class="hr-page-actions">
        <button class="hr-btn hr-btn-primary" onclick="openModal('uploadDocModal')">
            <i class="ph ph-upload-simple"></i>
            <span>Upload Document</span>
        </button>
    </div>
</div>

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Document Type</th>
                    <th>Employee</th>
                    <th>Branch</th>
                    <th>File Size</th>
                    <th>Expiry Date</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $doc)
                    <tr>
                        <td>
                            <strong>{{ $doc->title }}</strong><br>
                            <small style="color: #64748b;">{{ $doc->file_name }}</small>
                        </td>
                        <td><span class="hr-badge hr-badge-neutral">{{ $doc->document_type }}</span></td>
                        <td>{{ $doc->employee?->full_name }}</td>
                        <td>{{ $doc->employee?->branch?->name }}</td>
                        <td>{{ number_format(($doc->file_size ?? 0) / 1024, 1) }} KB</td>
                        <td>
                            @if($doc->expiry_date)
                                {{ \Carbon\Carbon::parse($doc->expiry_date)->format('M d, Y') }}
                            @else
                                <span style="color: #94a3b8;">None</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <form method="POST" action="{{ route('hr.people.documents.destroy', $doc->id) }}" onsubmit="return confirm('Delete this document?');" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="hr-btn hr-btn-danger hr-btn-sm" title="Delete">
                                    <i class="ph ph-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 30px;">No documents uploaded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($documents->hasPages())
        <div style="padding: 14px 20px; border-top: 1px solid #e2e8f0;">
            {{ $documents->links() }}
        </div>
    @endif
</div>

<!-- Modal: Upload Document -->
<div id="uploadDocModal" class="hr-modal-overlay">
    <div class="hr-modal">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-upload"></i> Upload Employee Document</span>
            <button class="icon-btn" onclick="closeModal('uploadDocModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.people.documents.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Employee *</label>
                    <select name="employee_id" class="hr-select" required>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->full_name }} ({{ $e->employee_id }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Document Type *</label>
                    <select name="document_type" class="hr-select" required>
                        <option value="Health Permit">Health Permit / Sanitary Card</option>
                        <option value="Food Handler Certificate">Food Handler Certificate</option>
                        <option value="Employment Contract">Employment Contract</option>
                        <option value="Government ID">Government ID</option>
                        <option value="NBI Clearance">NBI / Police Clearance</option>
                        <option value="Other">Other Document</option>
                    </select>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Document Title *</label>
                    <input type="text" name="title" class="hr-input" required placeholder="e.g. City Health Permit 2026">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Attach File * (Max 10MB)</label>
                    <input type="file" name="file" class="hr-input" required>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Expiry Date (Optional)</label>
                    <input type="date" name="expiry_date" class="hr-input">
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('uploadDocModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Upload Document</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
</script>
@endpush
@endsection
