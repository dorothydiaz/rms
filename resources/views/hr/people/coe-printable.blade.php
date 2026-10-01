<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Employment - {{ $employee->full_name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
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
            padding: 40px 20px;
            line-height: 1.6;
        }
        .no-print-toolbar {
            max-width: 800px;
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
            transition: all 0.15s ease;
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .btn-primary {
            background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%);
            color: #ffffff;
        }
        .btn-primary:hover {
            opacity: 0.92;
        }
        .coe-paper {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 60px 70px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            min-height: 1050px;
            position: relative;
        }
        .letterhead {
            text-align: center;
            padding-bottom: 24px;
            border-bottom: 2px solid #7c3aed;
            margin-bottom: 40px;
        }
        .company-name {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            font-weight: 700;
            color: #1e1b4b;
            letter-spacing: 0.5px;
        }
        .company-sub {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
        .document-title {
            text-align: center;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #0f172a;
            margin-bottom: 45px;
        }
        .salutation {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 24px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .body-text {
            font-size: 14.5px;
            color: #334155;
            text-align: justify;
            margin-bottom: 22px;
            line-height: 1.8;
        }
        .emp-highlight {
            font-weight: 700;
            color: #0f172a;
        }
        .signatory-block {
            margin-top: 60px;
            display: inline-block;
        }
        .signatory-line {
            width: 220px;
            border-bottom: 1.5px solid #0f172a;
            margin-bottom: 8px;
        }
        .signatory-name {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
        }
        .signatory-title {
            font-size: 13px;
            color: #64748b;
        }
        .watermark {
            position: absolute;
            bottom: 40px;
            left: 70px;
            right: 70px;
            border-top: 1px solid #f1f5f9;
            padding-top: 12px;
            font-size: 11px;
            color: #94a3b8;
            text-align: center;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print-toolbar {
                display: none !important;
            }
            .coe-paper {
                box-shadow: none;
                border: none;
                padding: 40px;
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
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="ph ph-printer"></i> Print / Save as PDF
            </button>
        </div>
    </div>

    <div class="coe-paper">
        <div class="letterhead">
            <div class="company-name">{{ $employee->company_or_agency ?? 'Restaurant Management System' }}</div>
            <div class="company-sub">
                {{ $employee->branch?->name ?? 'Main Branch' }} &bull; Philippines
            </div>
            <div class="company-sub" style="margin-top: 2px;">
                Human Resources Department &bull; Employee Records Division
            </div>
        </div>

        <div class="document-title">Certificate of Employment</div>

        <div class="salutation">To Whom It May Concern:</div>

        <div class="body-text">
            This is to certify that <span class="emp-highlight">{{ $employee->full_name }}</span> (Employee ID: <span class="emp-highlight">{{ $employee->employee_id }}</span>) is currently employed with <span class="emp-highlight">{{ $employee->company_or_agency ?? 'this Company' }}</span> holding the position of <span class="emp-highlight">{{ $employee->position?->name ?? 'Staff' }}</span> in the <span class="emp-highlight">{{ $employee->department?->name ?? 'Operations' }} Department</span>.
        </div>

        <div class="body-text">
            @php
                $hiredDate = $employee->date_hired ? \Carbon\Carbon::parse($employee->date_hired)->format('F d, Y') : 'the date of hire';
                $statusText = $employee->employment_status === 'Active' ? 'present' : ($employee->date_of_separation ? \Carbon\Carbon::parse($employee->date_of_separation)->format('F d, Y') : 'the date of separation');
            @endphp
            {{ $employee->gender === 'Female' ? 'She' : 'He' }} has been with the company since <span class="emp-highlight">{{ $hiredDate }}</span> up to <span class="emp-highlight">{{ $statusText }}</span>, serving under <span class="emp-highlight">{{ $employee->employment_type ?? 'Regular' }}</span> employment status.
        </div>

        @if($purpose)
            <div class="body-text">
                This certification is issued upon the request of <span class="emp-highlight">{{ $employee->full_name }}</span> for whatever legal purpose it may serve, particularly for <span class="emp-highlight">{{ $purpose }}</span>.
            </div>
        @else
            <div class="body-text">
                This certification is issued upon the request of the aforementioned employee for whatever legal purpose it may serve.
            </div>
        @endif

        <div class="body-text" style="margin-top: 30px;">
            Given this <span class="emp-highlight">{{ date('jS') }}</span> day of <span class="emp-highlight">{{ date('F, Y') }}</span> in the Republic of the Philippines.
        </div>

        <div class="signatory-block">
            <div class="signatory-line"></div>
            <div class="signatory-name">{{ $signatory }}</div>
            <div class="signatory-title">{{ $signatoryTitle }}</div>
            <div class="signatory-title" style="margin-top: 2px;">{{ $employee->company_or_agency ?? 'HR Department' }}</div>
        </div>

        <div class="watermark">
            Official Document &bull; Generated from HRIS 201 System &bull; Verification Code: COE-{{ $employee->employee_id }}-{{ date('Ymd') }}
        </div>
    </div>
</body>
</html>
