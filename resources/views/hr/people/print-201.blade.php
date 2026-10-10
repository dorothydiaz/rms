<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>201 File - {{ $employee->full_name }} ({{ $employee->employee_id }})</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f8fafc;
            color: #0f172a;
            padding: 30px 20px;
            font-size: 12.5px;
            line-height: 1.5;
        }
        .no-print-toolbar {
            max-width: 860px;
            margin: 0 auto 20px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            padding: 12px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }
        .btn-primary {
            background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%);
            color: #ffffff;
        }
        .sheet {
            max-width: 860px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px 50px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        .sheet-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #7c3aed;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .sheet-title {
            font-size: 20px;
            font-weight: 800;
            color: #1e1b4b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .sheet-sub {
            font-size: 12px;
            color: #64748b;
        }
        .sheet-badge {
            font-family: 'JetBrains Mono', monospace;
            background: #f1f5f9;
            color: #7c3aed;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 13px;
            border: 1px solid #e2e8f0;
        }
        .section-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #7c3aed;
            background: #faf5ff;
            border-left: 3px solid #7c3aed;
            padding: 5px 10px;
            margin: 20px 0 12px 0;
        }
        .data-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px 16px;
            margin-bottom: 14px;
        }
        .field {
            display: flex;
            flex-direction: column;
        }
        .label {
            font-size: 10.5px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .value {
            font-size: 12.5px;
            font-weight: 600;
            color: #0f172a;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
            margin-top: 8px;
        }
        .table-data th, .table-data td {
            border: 1px solid #e2e8f0;
            padding: 6px 10px;
            text-align: left;
        }
        .table-data th {
            background: #f8fafc;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            font-size: 10.5px;
        }
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px dashed #cbd5e1;
        }
        .sign-box {
            text-align: center;
        }
        .sign-line {
            border-bottom: 1.5px solid #0f172a;
            margin: 40px auto 8px auto;
            width: 80%;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print-toolbar {
                display: none !important;
            }
            .sheet {
                box-shadow: none;
                border: none;
                padding: 20px;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="no-print-toolbar">
        <a href="{{ route('hr.people.employees.show', $employee->id) }}" class="btn btn-secondary">
            <i class="ph ph-arrow-left"></i> Back to Profile
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="ph ph-printer"></i> Print / Save as PDF
        </button>
    </div>

    <div class="sheet">
        <div class="sheet-header">
            <div>
                <div class="sheet-title">Employee 201 File Master Record</div>
                <div class="sheet-sub">{{ $employee->company_or_agency ?? 'Restaurant Management System' }} &bull; Branch: {{ $employee->branch?->name ?? 'Main' }}</div>
            </div>
            <div class="sheet-badge">ID: {{ $employee->employee_id }}</div>
        </div>

        <!-- Section 1: Employment Profile -->
        <div class="section-title">1. Employment Information</div>
        <div class="data-grid">
            <div class="field">
                <span class="label">Position</span>
                <span class="value">{{ $employee->position?->name ?? 'N/A' }}</span>
            </div>
            <div class="field">
                <span class="label">Department</span>
                <span class="value">{{ $employee->department?->name ?? 'N/A' }}</span>
            </div>
            <div class="field">
                <span class="label">Branch</span>
                <span class="value">{{ $employee->branch?->name ?? 'N/A' }}</span>
            </div>
            <div class="field">
                <span class="label">Employment Type</span>
                <span class="value">{{ $employee->employment_type ?? 'Regular' }}</span>
            </div>
            <div class="field">
                <span class="label">Employment Status</span>
                <span class="value">{{ $employee->employment_status }}</span>
            </div>
            <div class="field">
                <span class="label">Supervisor</span>
                <span class="value">{{ $employee->supervisor?->full_name ?? 'None' }}</span>
            </div>
            <div class="field">
                <span class="label">Date Hired</span>
                <span class="value">{{ $employee->date_hired ? \Carbon\Carbon::parse($employee->date_hired)->format('M d, Y') : '—' }}</span>
            </div>
            <div class="field">
                <span class="label">Date Regularized</span>
                <span class="value">{{ $employee->date_of_regularization ? \Carbon\Carbon::parse($employee->date_of_regularization)->format('M d, Y') : 'Pending' }}</span>
            </div>
            <div class="field">
                <span class="label">Basic Salary</span>
                <span class="value">₱{{ number_format($employee->basic_salary, 2) }} ({{ $employee->pay_frequency ?? 'Semi-monthly' }})</span>
            </div>
            <div class="field">
                <span class="label">Payroll Type</span>
                <span class="value">{{ $employee->payroll_type }}</span>
            </div>
        </div>

        <!-- Section 2: Personal Profile -->
        <div class="section-title">2. Personal Profile</div>
        <div class="data-grid">
            <div class="field">
                <span class="label">Full Legal Name</span>
                <span class="value">{{ $employee->last_name }}, {{ $employee->first_name }} {{ $employee->middle_name }} {{ $employee->suffix }}</span>
            </div>
            <div class="field">
                <span class="label">Preferred Name</span>
                <span class="value">{{ $employee->preferred_name ?? '—' }}</span>
            </div>
            <div class="field">
                <span class="label">Date of Birth</span>
                <span class="value">{{ $employee->date_of_birth ? \Carbon\Carbon::parse($employee->date_of_birth)->format('M d, Y') : '—' }}</span>
            </div>
            <div class="field">
                <span class="label">Place of Birth</span>
                <span class="value">{{ $employee->birth_place ?? '—' }}</span>
            </div>
            <div class="field">
                <span class="label">Sex / Civil Status</span>
                <span class="value">{{ $employee->gender ?? '—' }} / {{ $employee->civil_status ?? 'Single' }}</span>
            </div>
            <div class="field">
                <span class="label">Nationality</span>
                <span class="value">{{ $employee->nationality ?? 'Filipino' }}</span>
            </div>
            <div class="field">
                <span class="label">Mobile Number</span>
                <span class="value">{{ $employee->mobile_number ?? '—' }}</span>
            </div>
            <div class="field">
                <span class="label">Company Email</span>
                <span class="value">{{ $employee->company_email ?? $employee->email ?? '—' }}</span>
            </div>
            <div class="field">
                <span class="label">Personal Email</span>
                <span class="value">{{ $employee->personal_email ?? $employee->email ?? '—' }}</span>
            </div>
            <div class="field" style="grid-column: 1 / -1;">
                <span class="label">Current Residential Address</span>
                <span class="value">{{ $employee->address ?? '—' }}</span>
            </div>
            <div class="field" style="grid-column: 1 / -1;">
                <span class="label">Permanent Address</span>
                <span class="value">{{ $employee->permanent_address ?? $employee->address ?? '—' }}</span>
            </div>
        </div>

        <!-- Section 3: Philippine Government IDs -->
        <div class="section-title">3. Philippine Statutory & Government Identification</div>
        <div class="data-grid">
            <div class="field">
                <span class="label">SSS Number</span>
                <span class="value">{{ $employee->sss_number ?? '—' }} ({{ $employee->sss_verified ? 'Verified' : 'Unverified' }})</span>
            </div>
            <div class="field">
                <span class="label">PhilHealth Number</span>
                <span class="value">{{ $employee->philhealth_number ?? '—' }} ({{ $employee->philhealth_verified ? 'Verified' : 'Unverified' }})</span>
            </div>
            <div class="field">
                <span class="label">Pag-IBIG / MID Number</span>
                <span class="value">{{ $employee->pagibig_number ?? '—' }} ({{ $employee->pagibig_verified ? 'Verified' : 'Unverified' }})</span>
            </div>
            <div class="field">
                <span class="label">BIR / TIN</span>
                <span class="value">{{ $employee->tin_number ?? '—' }} (RDO: {{ $employee->rdo_code ?? 'Default' }})</span>
            </div>
            <div class="field">
                <span class="label">PhilSys / National ID</span>
                <span class="value">{{ $employee->philsys_id ?? '—' }}</span>
            </div>
            <div class="field">
                <span class="label">Passport / Driver's License</span>
                <span class="value">{{ $employee->passport_number ?? $employee->driver_license ?? '—' }}</span>
            </div>
        </div>

        <!-- Section 4: Emergency Contacts & Family -->
        <div class="section-title">4. Emergency Contacts</div>
        @if($employee->emergencyContacts->count() > 0)
            <table class="table-data">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Relationship</th>
                        <th>Mobile Number</th>
                        <th>Address</th>
                        <th>Primary</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employee->emergencyContacts as $ec)
                        <tr>
                            <td><strong>{{ $ec->name }}</strong></td>
                            <td>{{ $ec->relationship }}</td>
                            <td>{{ $ec->mobile_number }}</td>
                            <td>{{ $ec->address ?? '—' }}</td>
                            <td>{{ $ec->is_primary ? 'Yes (Primary)' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div style="font-size: 11.5px; color: #64748b; font-style: italic;">No emergency contacts registered.</div>
        @endif

        <!-- Section 5: 201 File Attached Documents -->
        <div class="section-title">5. 201 Document Filing Checklist</div>
        @if($employee->documents->count() > 0)
            <table class="table-data">
                <thead>
                    <tr>
                        <th>Document Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Expiry Date</th>
                        <th>Date Uploaded</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employee->documents as $doc)
                        <tr>
                            <td>{{ $doc->document_name }}</td>
                            <td>{{ $doc->category }}</td>
                            <td><strong>{{ $doc->status }}</strong></td>
                            <td>{{ $doc->expiry_date ? \Carbon\Carbon::parse($doc->expiry_date)->format('M d, Y') : 'N/A' }}</td>
                            <td>{{ $doc->created_at->format('M d, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div style="font-size: 11.5px; color: #64748b; font-style: italic;">No uploaded documents found in 201 file folder.</div>
        @endif

        <div class="signatures">
            <div class="sign-box">
                <div class="sign-line"></div>
                <div style="font-weight: 700;">{{ $employee->full_name }}</div>
                <div style="font-size: 11px; color: #64748b;">Employee Signature over Printed Name</div>
            </div>
            <div class="sign-box">
                <div class="sign-line"></div>
                <div style="font-weight: 700;">Human Resources Officer</div>
                <div style="font-size: 11px; color: #64748b;">HR & Workforce Administration</div>
            </div>
        </div>
    </div>
</body>
</html>
