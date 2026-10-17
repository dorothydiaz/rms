<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate of Daily Time Record — {{ $employee->employee_id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1e293b; padding: 20px; margin: 0 auto; max-width: 800px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0f172a; padding-bottom: 10px; }
        .header h1 { font-size: 16px; margin: 0; text-transform: uppercase; }
        .header h2 { font-size: 13px; margin: 4px 0 0 0; color: #475569; font-weight: normal; }
        .info-grid { display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 11.5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 8px; text-align: center; }
        th { background: #f1f5f9; font-weight: bold; }
        .cert-statement { margin-top: 25px; font-size: 10.5px; text-align: justify; line-height: 1.5; }
        .signatures { display: flex; justify-content: space-between; margin-top: 50px; }
        .sig-box { width: 45%; text-align: center; border-top: 1px solid #0f172a; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>CERTIFICATE OF DAILY TIME RECORD</h1>
        <h2>Civil Service Form 48 / DOLE Standard DTR</h2>
    </div>

    <div class="info-grid">
        <div>
            <div><strong>Employee:</strong> {{ $employee->first_name }} {{ $employee->last_name }}</div>
            <div><strong>Employee ID:</strong> {{ $employee->employee_id }}</div>
            <div><strong>Position:</strong> {{ $employee->position->name ?? 'Staff' }}</div>
        </div>
        <div style="text-align: right;">
            <div><strong>Period:</strong> {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</div>
            <div><strong>Department:</strong> {{ $employee->department->name ?? 'N/A' }}</div>
            <div><strong>Branch:</strong> {{ $employee->branch->branch_name ?? 'N/A' }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2">Day</th>
                <th colspan="2">A.M.</th>
                <th colspan="2">P.M.</th>
                <th colspan="2">Undertime</th>
                <th rowspan="2">Overtime</th>
                <th rowspan="2">Remarks</th>
            </tr>
            <tr>
                <th>Arrival</th>
                <th>Departure</th>
                <th>Arrival</th>
                <th>Departure</th>
                <th>Hours</th>
                <th>Mins</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $rec)
                <tr>
                    <td><strong>{{ \Carbon\Carbon::parse($rec->date)->format('j') }}</strong></td>
                    <td>{{ $rec->time_in ? \Carbon\Carbon::parse($rec->time_in)->format('h:i A') : '' }}</td>
                    <td>{{ $rec->break_out ? \Carbon\Carbon::parse($rec->break_out)->format('h:i A') : '' }}</td>
                    <td>{{ $rec->break_in ? \Carbon\Carbon::parse($rec->break_in)->format('h:i A') : '' }}</td>
                    <td>{{ $rec->time_out ? \Carbon\Carbon::parse($rec->time_out)->format('h:i A') : '' }}</td>
                    <td></td>
                    <td>{{ $rec->undertime_minutes > 0 ? $rec->undertime_minutes : '' }}</td>
                    <td>{{ $rec->overtime_hours > 0 ? number_format($rec->overtime_hours, 1) : '' }}</td>
                    <td>{{ $rec->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="cert-statement">
        I CERTIFY on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.
    </div>

    <div class="signatures">
        <div class="sig-box">
            <strong>{{ $employee->first_name }} {{ $employee->last_name }}</strong><br>
            <span>Employee Signature</span>
        </div>
        <div class="sig-box">
            <strong>{{ $employee->supervisor ? ($employee->supervisor->first_name . ' ' . $employee->supervisor->last_name) : 'Authorized Executive' }}</strong><br>
            <span>In Charge / Immediate Supervisor</span>
        </div>
    </div>

    <script>
        window.onload = function() {
            // Optional auto print if opened standalone
            if (window.location.search.includes('print=1')) {
                window.print();
            }
        };
    </script>
</body>
</html>
