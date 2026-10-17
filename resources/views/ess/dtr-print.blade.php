<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Time Record - {{ $employee->first_name }} {{ $employee->last_name }} ({{ $month }})</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; color: #1e293b; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h2 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 3px 0; font-size: 11px; }
        .meta-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .meta-table td { padding: 4px; font-size: 11px; }
        .dtr-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .dtr-table th, .dtr-table td { border: 1px solid #000; padding: 4px 6px; text-align: center; }
        .dtr-table th { background: #f1f5f9; font-weight: bold; }
        .signatures { display: flex; justify-content: space-between; margin-top: 40px; padding: 0 40px; }
        .sig-block { text-align: center; width: 200px; border-top: 1px solid #000; padding-top: 5px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Print DTR</button>
    </div>

    <div class="header">
        <h2>{{ $employee->company?->name ?? 'RESTAURANT MANAGEMENT SYSTEM' }}</h2>
        <p><strong>DAILY TIME RECORD (CIVIL SERVICE & DOLE COMPLIANT)</strong></p>
        <p>Month of {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</p>
    </div>

    <table class="meta-table">
        <tr>
            <td width="15%"><strong>Employee Name:</strong></td>
            <td width="35%">{{ $employee->last_name }}, {{ $employee->first_name }} {{ $employee->middle_name }}</td>
            <td width="15%"><strong>Employee ID:</strong></td>
            <td width="35%">{{ $employee->employee_id }}</td>
        </tr>
        <tr>
            <td><strong>Branch / Station:</strong></td>
            <td>{{ $employee->branch?->branch_name ?? 'Makati Flagship Branch' }}</td>
            <td><strong>Department / Role:</strong></td>
            <td>{{ $employee->department?->name ?? 'Front of House' }} - {{ $employee->position?->name ?? 'Staff' }}</td>
        </tr>
    </table>

    <table class="dtr-table">
        <thead>
            <tr>
                <th rowspan="2">Day</th>
                <th colspan="2">AM</th>
                <th colspan="2">PM</th>
                <th rowspan="2">Reg. Hrs</th>
                <th rowspan="2">Overtime</th>
                <th rowspan="2">Undertime / Late</th>
                <th rowspan="2">Remarks / Classification</th>
            </tr>
            <tr>
                <th>Arrival</th>
                <th>Departure</th>
                <th>Arrival</th>
                <th>Departure</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $r)
                <tr>
                    <td>{{ $r->date->format('d (D)') }}</td>
                    <td>{{ $r->time_in ?? '--' }}</td>
                    <td>12:00</td>
                    <td>13:00</td>
                    <td>{{ $r->time_out ?? '--' }}</td>
                    <td>{{ number_format($r->regular_hours, 2) }}</td>
                    <td>{{ $r->overtime_hours > 0 ? $r->overtime_hours : '-' }}</td>
                    <td>{{ ($r->late_minutes + $r->undertime_minutes) > 0 ? ($r->late_minutes + $r->undertime_minutes) . 'm' : '-' }}</td>
                    <td>{{ $r->holiday_type ? $r->holiday_type . ' Holiday' : ($r->is_rest_day ? 'Rest Day' : $r->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="font-size: 10px; font-style: italic;">I certify on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.</p>

    <div class="signatures">
        <div class="sig-block">
            <strong>{{ $employee->first_name }} {{ $employee->last_name }}</strong><br>
            Employee Signature
        </div>
        <div class="sig-block">
            <strong>{{ $employee->supervisor ? $employee->supervisor->first_name . ' ' . $employee->supervisor->last_name : 'Authorized Supervisor' }}</strong><br>
            In-Charge / Supervisor
        </div>
    </div>
</body>
</html>
