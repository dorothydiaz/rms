<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Time Record</title>
    <style>
        @page {
            size: letter portrait;
            margin-top: 18pt;
            margin-left: 20pt;
            margin-right: 20pt;
            margin-bottom: 18pt;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 8.5pt;
            color: #0f172a;
            line-height: 1.3;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        .dtr-page {
            width: 100%;
            page-break-after: always;
        }

        .dtr-page:last-child {
            page-break-after: avoid;
        }

        /* Top Header Letterhead with Purple/Violet UI Theme */
        .header-container {
            width: 100%;
            margin-bottom: 8pt;
            border-bottom: 2.5pt solid #7c3aed;
            padding-bottom: 5pt;
        }

        .title-table {
            width: 100%;
            border-collapse: collapse;
        }

        .title-table td {
            vertical-align: middle;
            padding: 0;
        }

        .doc-title {
            font-size: 15pt;
            font-weight: bold;
            letter-spacing: 2px;
            color: #581c87;
            text-transform: uppercase;
        }

        .doc-subtitle {
            font-size: 8.5pt;
            letter-spacing: 0.5px;
            color: #7c3aed;
            margin-top: 2pt;
            font-weight: bold;
        }

        .header-meta-right {
            text-align: right;
            font-size: 8.5pt;
            color: #334155;
            line-height: 1.4;
        }

        .header-meta-label {
            font-weight: bold;
            color: #6b21a8;
        }

        /* Integrated Company & Employee Information Card */
        .info-card {
            width: 100%;
            border-collapse: collapse;
            border: 1.2pt solid #ddd6fe;
            border-left: 4pt solid #7c3aed;
            background-color: #faf5ff;
            margin-bottom: 8pt;
        }

        .info-card td {
            font-size: 8.5pt;
            line-height: 1.35;
            vertical-align: top;
            padding: 3pt 8pt;
        }

        .info-label {
            font-weight: bold;
            color: #6b21a8;
            white-space: nowrap;
        }

        .info-sep {
            color: #7c3aed;
            font-weight: bold;
            width: 8pt;
            text-align: center;
        }

        .info-val {
            color: #0f172a;
        }

        .info-val-bold {
            font-weight: bold;
            color: #0f172a;
            font-size: 9pt;
        }

        .info-divider {
            border-top: 0.8pt solid #ede9fe;
        }

        /* DTR Grid Table */
        .dtr-grid {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            font-family: 'Courier New', Courier, monospace;
            font-size: 8.5pt;
            line-height: 1.25;
            border: 1.5pt solid #6b21a8;
        }

        .dtr-grid th {
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
            vertical-align: middle;
            white-space: nowrap;
        }

        /* Group Headers in Deep Purple */
        .th-group {
            background-color: #6b21a8;
            color: #ffffff;
            letter-spacing: 0.8px;
            padding: 4pt 1pt;
            font-size: 8.5pt;
            border-bottom: 1.2pt solid #4c1d95;
        }

        /* Sub-Headers (Row 2) - Soft Lavender Tint */
        .th-sub {
            background-color: #f3e8ff;
            color: #581c87;
            border-bottom: 1.5pt solid #6b21a8;
            padding: 3.5pt 1pt;
            font-size: 8pt;
        }

        /* Section Vertical Dividers */
        .sec-div {
            border-right: 1.5pt solid #6b21a8;
        }

        .col-div {
            border-right: 0.5pt solid #ddd6fe;
        }

        /* Table Body Rows */
        .dtr-grid td {
            padding: 6.2pt 1.5pt;
            white-space: nowrap;
            vertical-align: middle;
            font-size: 7.6pt;
            border-bottom: 0.5pt solid #e2e8f0;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .row-even {
            background-color: #ffffff;
        }

        .row-odd {
            background-color: #faf5ff;
        }

        .row-restday {
            background-color: #fffbeb;
        }

        .badge-rd {
            color: #d97706;
            font-weight: bold;
        }

        .val-late {
            color: #d97706;
            font-weight: bold;
        }

        .val-ut {
            color: #ef4444;
            font-weight: bold;
        }

        .val-ot {
            color: #6366f1;
            font-weight: bold;
        }

        .val-abs {
            color: #ef4444;
            font-weight: bold;
        }

        .val-remarks {
            color: #7c3aed;
            font-weight: bold;
        }

        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Totals Row with Royal Purple Accents */
        .totals-row td {
            background-color: #f5f3ff;
            color: #0f172a;
            font-weight: bold;
            padding-top: 5.5pt;
            padding-bottom: 5.5pt;
            font-size: 7.6pt;
            border-top: 1.5pt solid #6b21a8;
            border-bottom: 3pt double #6b21a8;
            white-space: nowrap;
        }

        .totals-label {
            letter-spacing: 0.8px;
            font-size: 7.6pt;
            padding-right: 5pt;
            color: #6b21a8;
        }

        .totals-num {
            color: #0f172a;
            font-weight: bold;
        }

        /* Signatures Section */
        .signatures-section {
            width: 100%;
            margin-top: 12pt;
            border: 1pt solid #ddd6fe;
            border-top: 2.5pt solid #7c3aed;
            background-color: #ffffff;
            padding: 8pt 10pt;
        }

        .cert-statement {
            font-size: 7.8pt;
            text-align: center;
            font-style: italic;
            margin-bottom: 12pt;
            color: #6b21a8;
            background-color: #f5f3ff;
            padding: 3.5pt 8pt;
            border-radius: 2pt;
            border: 0.5pt solid #ddd6fe;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signatures-table td {
            vertical-align: top;
            font-size: 7.8pt;
            text-align: center;
            padding: 0 10pt;
        }

        .sig-title {
            font-weight: bold;
            font-size: 7.5pt;
            text-transform: uppercase;
            margin-bottom: 34pt;
            letter-spacing: 0.5px;
            color: #6b21a8;
        }

        .sig-line {
            border-top: 1.2pt solid #7c3aed;
            width: 88%;
            margin: 0 auto 3pt auto;
        }

        .sig-name {
            font-size: 8.2pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            margin-bottom: 2pt;
        }

        .sig-role {
            font-size: 7.2pt;
            color: #64748b;
            margin-bottom: 2pt;
        }

        .sig-date {
            font-size: 7pt;
            color: #94a3b8;
        }

        /* Bottom Legend Strip */
        .legend-strip {
            width: 100%;
            margin-top: 6pt;
            border: 0.8pt solid #ddd6fe;
            background-color: #faf5ff;
            padding: 3.5pt 6pt;
            font-size: 7pt;
            color: #475569;
            text-align: center;
            line-height: 1.35;
        }

        .legend-item {
            display: inline-block;
            margin: 0 4pt;
            white-space: nowrap;
        }

        .legend-tag {
            font-weight: bold;
            color: #6b21a8;
        }
    </style>
</head>
<body>

@php $globalPage = 0; @endphp

@foreach($employeesData as $empIdx => $item)
    @php
        // Paginate up to 16 dates per page
        $dayChunks = array_chunk($item['days'], 16);
    @endphp

    @foreach($dayChunks as $chunkIdx => $chunkDays)
        @php
            $globalPage++;

            // Calculate chunk page totals for these up to 16 days
            $chunkRegHrs = 0.0;
            $chunkLateMins = 0;
            $chunkUtMins = 0;
            $chunkAbsence = 0.0;
            $chunkNet = 0.0;
            $chunkOt = 0.0;

            foreach ($chunkDays as $cd) {
                if ($cd['reg_hrs'] !== null && $cd['reg_hrs'] !== '') {
                    $chunkRegHrs += (float)$cd['reg_hrs'];
                }
                $chunkLateMins += (int)$cd['late_mins'];
                $chunkUtMins += (int)$cd['ut_mins'];
                $chunkAbsence += (float)$cd['absence'];
                if ($cd['net'] !== null && $cd['net'] !== '') {
                    $chunkNet += (float)$cd['net'];
                }
                $chunkOt += (float)$cd['ot'];
            }
        @endphp

        <div class="dtr-page">
            <!-- Top Header Letterhead -->
            <div class="header-container">
                <table class="title-table">
                    <tr>
                        <td style="width: 58%;">
                            <div class="doc-title">DAILY TIME RECORD</div>
                            <div class="doc-subtitle">Civil Service Form No. 48 / Official Timekeeping Ledger</div>
                        </td>
                        <td style="width: 42%;" class="header-meta-right">
                            <div><span class="header-meta-label">CUT-OFF:</span> {{ $item['cutoff'] }}</div>
                            <div><span class="header-meta-label">PAGE:</span> {{ $globalPage }} &nbsp;|&nbsp; <span class="header-meta-label">PRINTED:</span> {{ $runDate }} {{ $runTime }}</div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Integrated Company & Employee Information Card -->
            <table class="info-card">
                <tr>
                    <td style="width: 14%;" class="info-label">COMPANY</td>
                    <td class="info-sep">:</td>
                    <td style="width: 36%;" class="info-val">{{ $item['company'] }}</td>
                    <td style="width: 14%;" class="info-label">EMPLOYEE ID</td>
                    <td class="info-sep">:</td>
                    <td style="width: 36%;" class="info-val-bold">{{ $item['employee_id'] }}</td>
                </tr>
                <tr>
                    <td class="info-label">BRANCH</td>
                    <td class="info-sep">:</td>
                    <td class="info-val">{{ $item['branch'] }}</td>
                    <td class="info-label">EMPLOYEE NAME</td>
                    <td class="info-sep">:</td>
                    <td class="info-val-bold">{{ $item['employee_name'] }}</td>
                </tr>
                <tr class="info-divider">
                    <td class="info-label">DEPARTMENT</td>
                    <td class="info-sep">:</td>
                    <td class="info-val">{{ $item['department'] }}</td>
                    <td class="info-label">REPORT TYPE</td>
                    <td class="info-sep">:</td>
                    <td class="info-val">OFFICIAL ATTENDANCE RECORD</td>
                </tr>
            </table>

            <!-- DTR Table Grid (Up to 16 rows per page) -->
            <table class="dtr-grid">
                <colgroup>
                    <col style="width: 6.6%;">
                    <col style="width: 4.0%;">
                    <col style="width: 4.8%;">
                    <col style="width: 5.4%;">
                    <col style="width: 5.4%;">
                    <col style="width: 5.4%;">
                    <col style="width: 5.4%;">
                    <col style="width: 5.4%;">
                    <col style="width: 5.4%;">
                    <col style="width: 5.6%;">
                    <col style="width: 4.6%;">
                    <col style="width: 4.6%;">
                    <col style="width: 4.6%;">
                    <col style="width: 5.6%;">
                    <col style="width: 4.6%;">
                    <col style="width: 23.0%;">
                </colgroup>
                <thead>
                    <!-- Header Row 1: Unified Purple Section Groups -->
                    <tr>
                        <th colspan="3" class="th-group sec-div text-center">SCHEDULE</th>
                        <th colspan="6" class="th-group sec-div text-center">ACTUAL TIME ENTRIES</th>
                        <th colspan="6" class="th-group sec-div text-center">HOURS &amp; MINUTES COMPUTATION</th>
                        <th class="th-group text-center">NOTES</th>
                    </tr>
                    <!-- Header Row 2: Clean Sub-Headers -->
                    <tr>
                        <th class="th-sub col-div text-left" style="padding-left: 3pt;">DATE</th>
                        <th class="th-sub col-div text-center">DAY</th>
                        <th class="th-sub sec-div text-center">SHIFT</th>
                        <th class="th-sub col-div text-center">IN 1</th>
                        <th class="th-sub col-div text-center">OUT 1</th>
                        <th class="th-sub col-div text-center">IN 2</th>
                        <th class="th-sub col-div text-center">OUT 2</th>
                        <th class="th-sub col-div text-center">IN 3</th>
                        <th class="th-sub sec-div text-center">OUT 3</th>
                        <th class="th-sub col-div text-right" style="padding-right: 2pt;">REG</th>
                        <th class="th-sub col-div text-right" style="padding-right: 2pt;">LATE</th>
                        <th class="th-sub col-div text-right" style="padding-right: 2pt;">UT</th>
                        <th class="th-sub col-div text-right" style="padding-right: 2pt;">ABS</th>
                        <th class="th-sub col-div text-right" style="padding-right: 2pt;">NET</th>
                        <th class="th-sub sec-div text-right" style="padding-right: 2pt;">OT</th>
                        <th class="th-sub text-left" style="padding-left: 5pt;">REMARKS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($chunkDays as $rowIdx => $day)
                        @php
                            $isRestDay = ($day['shift_id'] === 'RD');
                            $rowClass = $isRestDay ? 'row-restday' : ($rowIdx % 2 === 0 ? 'row-even' : 'row-odd');
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="col-div text-left" style="padding-left: 3pt;">{{ $day['date_formatted'] }}</td>
                            <td class="col-div text-center">{{ $day['day_short'] }}</td>
                            <td class="sec-div text-center" style="font-weight: bold; color: {{ $isRestDay ? '#d97706' : '#6b21a8' }};">{{ $day['shift_id'] }}</td>

                            @if($isRestDay)
                                <!-- Rest Day: warm cream row, clear status badge in remarks -->
                                <td class="col-div text-center"></td>
                                <td class="col-div text-center"></td>
                                <td class="col-div text-center"></td>
                                <td class="col-div text-center"></td>
                                <td class="col-div text-center"></td>
                                <td class="sec-div text-center"></td>
                                <td class="col-div text-right"></td>
                                <td class="col-div text-right"></td>
                                <td class="col-div text-right"></td>
                                <td class="col-div text-right"></td>
                                <td class="col-div text-right"></td>
                                <td class="sec-div text-right"></td>
                                <td class="text-left" style="padding-left: 5pt;"><span class="badge-rd">{{ $day['remarks'] ?: 'REST DAY' }}</span></td>
                            @else
                                <!-- 6 Actual Time Entry Punches -->
                                <td class="col-div text-center">{{ $day['in_1'] ?: '' }}</td>
                                <td class="col-div text-center">{{ $day['out_1'] ?: '' }}</td>
                                <td class="col-div text-center">{{ $day['in_2'] ?: '' }}</td>
                                <td class="col-div text-center">{{ $day['out_2'] ?: '' }}</td>
                                <td class="col-div text-center">{{ $day['in_3'] ?: '' }}</td>
                                <td class="sec-div text-center">{{ $day['out_3'] ?: '' }}</td>

                                <!-- Calculated Metrics Matching UI Color Cues -->
                                <td class="col-div text-right" style="padding-right: 2pt;">{{ $day['reg_hrs'] !== null && $day['reg_hrs'] !== '' ? number_format((float)$day['reg_hrs'], 2) : '' }}</td>
                                <td class="col-div text-right {{ $day['late_mins'] > 0 ? 'val-late' : '' }}" style="padding-right: 2pt;">{{ $day['late_mins'] > 0 ? $day['late_mins'] : '' }}</td>
                                <td class="col-div text-right {{ $day['ut_mins'] > 0 ? 'val-ut' : '' }}" style="padding-right: 2pt;">{{ $day['ut_mins'] > 0 ? $day['ut_mins'] : '' }}</td>
                                <td class="col-div text-right {{ $day['absence'] > 0 ? 'val-abs' : '' }}" style="padding-right: 2pt;">{{ $day['absence'] > 0 ? number_format((float)$day['absence'], 2) : '' }}</td>
                                <td class="col-div text-right" style="padding-right: 2pt; font-weight: bold;">{{ $day['net'] !== null && $day['net'] !== '' ? number_format((float)$day['net'], 2) : '' }}</td>
                                <td class="sec-div text-right {{ $day['ot'] > 0 ? 'val-ot' : '' }}" style="padding-right: 2pt;">{{ $day['ot'] > 0 ? number_format((float)$day['ot'], 2) : '' }}</td>
                                <td class="text-left val-remarks" style="padding-left: 5pt;">{{ $day['remarks'] }}</td>
                            @endif
                        </tr>
                    @endforeach

                    <!-- Totals Row with Clean Accounting Rule -->
                    <tr class="totals-row">
                        <td colspan="9" class="sec-div text-right totals-label">PAGE TOTALS:</td>
                        <td class="col-div text-right totals-num" style="padding-right: 2pt;">{{ number_format((float)$chunkRegHrs, 2) }}</td>
                        <td class="col-div text-right totals-num" style="padding-right: 2pt;">{{ $chunkLateMins > 0 ? $chunkLateMins : '0' }}</td>
                        <td class="col-div text-right totals-num" style="padding-right: 2pt;">{{ $chunkUtMins > 0 ? $chunkUtMins : '0' }}</td>
                        <td class="col-div text-right totals-num" style="padding-right: 2pt;">{{ number_format((float)$chunkAbsence, 2) }}</td>
                        <td class="col-div text-right totals-num" style="padding-right: 2pt;">{{ number_format((float)$chunkNet, 2) }}</td>
                        <td class="sec-div text-right totals-num" style="padding-right: 2pt;">{{ number_format((float)$chunkOt, 2) }}</td>
                        <td class="text-left" style="padding-left: 5pt; color: #7c3aed; font-size: 7pt; font-weight: bold;">VERIFIED</td>
                    </tr>
                </tbody>
            </table>

            <!-- Signatures Section with Certification -->
            <div class="signatures-section">
                <div class="cert-statement">
                    I certify on my honor that the above is a true and correct report of the hours of work performed.
                </div>
                <table class="signatures-table">
                    <tr>
                        <td style="width: 33%;">
                            <div class="sig-title">PREPARED BY</div>
                            <div class="sig-line"></div>
                            <div class="sig-name">TIMEKEEPING / HR</div>
                            <div class="sig-role">Authorized Representative</div>
                            <div class="sig-date">Date: ____________________</div>
                        </td>
                        <td style="width: 34%;">
                            <div class="sig-title">EMPLOYEE CONFORME</div>
                            <div class="sig-line"></div>
                            <div class="sig-name">{{ $item['employee_name'] }}</div>
                            <div class="sig-role">Signature over Printed Name</div>
                            <div class="sig-date">Date: ____________________</div>
                        </td>
                        <td style="width: 33%;">
                            <div class="sig-title">APPROVED BY</div>
                            <div class="sig-line"></div>
                            <div class="sig-name">DEPARTMENT HEAD</div>
                            <div class="sig-role">Immediate Supervisor / Manager</div>
                            <div class="sig-date">Date: ____________________</div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Bottom Legend Strip -->
            <div class="legend-strip">
                <span class="legend-item"><span class="legend-tag">DS8:</span> Regular Shift (8H)</span>
                <span class="legend-item"><span class="legend-tag">RD:</span> Rest Day</span>
                <span class="legend-item"><span class="legend-tag">LATE:</span> Tardy Mins</span>
                <span class="legend-item"><span class="legend-tag">UT:</span> Undertime Mins</span>
                <span class="legend-item"><span class="legend-tag">ABS:</span> Absence Days</span>
                <span class="legend-item"><span class="legend-tag">OT:</span> Overtime Hrs</span>
                <span class="legend-item"><span class="legend-tag">VL/SL:</span> Approved Leaves</span>
                <span class="legend-item"><span class="legend-tag">LH/SH:</span> Holidays</span>
            </div>
        </div>
    @endforeach
@endforeach

</body>
</html>
