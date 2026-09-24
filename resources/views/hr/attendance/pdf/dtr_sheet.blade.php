<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Time Record</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap');

        @page {
            size: letter portrait;
            margin-top: 24pt;
            margin-left: 28pt;
            margin-right: 28pt;
            margin-bottom: 50pt;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 10pt;
            color: #000000;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        /* Fixed Signatures Footer - Pinned to the bottom of each page */
        .signatures-footer {
            position: fixed;
            bottom: -35pt;
            left: 0;
            right: 0;
            height: 32pt;
            font-size: 10pt;
            line-height: 1.5;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signatures-table td {
            width: 33.33%;
            padding: 0 4px;
            vertical-align: bottom;
            white-space: nowrap;
            font-size: 10pt;
            line-height: 1.5;
        }

        .sig-line {
            display: inline-block;
            letter-spacing: -1px;
        }

        .dtr-page {
            width: 100%;
            page-break-after: always;
        }

        .dtr-page:last-child {
            page-break-after: avoid;
        }

        /* Top Header Metadata Table */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .header-table td {
            vertical-align: top;
            padding: 1px 0;
            font-size: 10pt;
            line-height: 1.5;
        }

        .doc-title {
            font-size: 11pt;
            font-weight: 600;
            margin-bottom: 2px;
            line-height: 1.5;
        }

        /* Employee Information Section */
        .emp-info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .emp-info-table td {
            padding: 1px 0;
            font-size: 10pt;
            line-height: 1.5;
            vertical-align: top;
        }

        /* DTR Grid Table */
        .dtr-grid {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            font-size: 10pt;
            line-height: 1.5;
        }

        .dtr-grid th {
            font-weight: 500;
            padding: 1px 1px;
            vertical-align: bottom;
            white-space: nowrap;
            font-size: 10pt;
            line-height: 1.5;
        }

        .header-row-1 th {
            padding-bottom: 2px;
        }

        .header-divider-punches {
            border-bottom: 1px solid #000000;
        }

        .header-row-2 th {
            border-bottom: 1.5px solid #000000;
            padding-top: 1px;
            padding-bottom: 4px;
        }

        .dtr-grid td {
            padding: 3.5px 1px;
            white-space: nowrap;
            vertical-align: middle;
            font-size: 10pt;
            line-height: 1.5;
        }

        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Totals Row */
        .totals-row td {
            padding-top: 6px;
            padding-bottom: 8px;
            font-size: 10pt;
            line-height: 1.5;
            white-space: nowrap;
        }

        .totals-divider {
            border-top: 1.5px solid #000000;
        }
    </style>
</head>
<body>

<!-- Fixed Signatures Footer stuck at the bottom of every page -->
<div class="signatures-footer">
    <table class="signatures-table">
        <tr>
            <td>
                Processed by/Date: <span class="sig-line">____________________</span>
            </td>
            <td>
                Confirmed by/Date: <span class="sig-line">____________________</span>
            </td>
            <td>
                Noted by/Date: <span class="sig-line">____________________</span>
            </td>
        </tr>
    </table>
</div>

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
            <!-- Header Block -->
            <table class="header-table">
                <tr>
                    <td style="width: 58%;">
                        <div class="doc-title">Daily Time Record</div>
                        <div>Company : &nbsp;{{ $item['company'] }}</div>
                        <div>Branch &nbsp;: &nbsp;{{ $item['branch'] }}</div>
                        <div>Cut off : &nbsp;{{ $item['cutoff'] }}</div>
                    </td>
                    <td style="width: 42%; text-align: right;">
                        <div>Page &nbsp;&nbsp;&nbsp;: &nbsp;{{ $globalPage }}</div>
                        <div>Run Date: &nbsp;{{ $runDate }}</div>
                        <div>Run Time: &nbsp;{{ $runTime }}</div>
                    </td>
                </tr>
            </table>

            <!-- Employee Information Block -->
            <table class="emp-info-table">
                <tr>
                    <td style="width: 175px;">Employee Number</td>
                    <td style="width: 15px;">:</td>
                    <td>{{ $item['employee_id'] }}</td>
                </tr>
                <tr>
                    <td>Employee Name</td>
                    <td>:</td>
                    <td>{{ $item['employee_name'] }}</td>
                </tr>
                <tr>
                    <td>Department</td>
                    <td>:</td>
                    <td>{{ $item['department'] }}</td>
                </tr>
            </table>

            <!-- DTR Table Grid (Up to 16 rows per page) -->
            <table class="dtr-grid">
                <thead>
                    <!-- Header Line 1 -->
                    <tr class="header-row-1">
                        <th style="width: 7.5%; text-align: left;"></th>
                        <th style="width: 4.5%; text-align: center;"></th>
                        <th style="width: 7%; text-align: center;">Shift</th>
                        <th colspan="6" class="header-divider-punches" style="width: 34.8%; text-align: center;">ACTUAL TIME ENTRIES</th>
                        <th style="width: 6.5%; text-align: right;">Reg</th>
                        <th style="width: 5.5%; text-align: right;">Tardy</th>
                        <th style="width: 5%; text-align: right;">UT</th>
                        <th style="width: 5.5%; text-align: right;"></th>
                        <th style="width: 6.5%; text-align: right;"></th>
                        <th style="width: 5.5%; text-align: right;"></th>
                        <th style="width: 11.7%; text-align: left; padding-left: 6px;">Remark</th>
                    </tr>
                    <!-- Header Line 2 -->
                    <tr class="header-row-2">
                        <th class="text-left">Date</th>
                        <th class="text-center">Day</th>
                        <th class="text-center">ID</th>
                        <th style="width: 5.8%; text-align: center;">IN</th>
                        <th style="width: 5.8%; text-align: center;">OUT</th>
                        <th style="width: 5.8%; text-align: center;">IN</th>
                        <th style="width: 5.8%; text-align: center;">OUT</th>
                        <th style="width: 5.8%; text-align: center;">IN</th>
                        <th style="width: 5.8%; text-align: center;">OUT</th>
                        <th class="text-right">Hrs</th>
                        <th class="text-right">Mins</th>
                        <th class="text-right">Mins</th>
                        <th class="text-right">Abs.</th>
                        <th class="text-right">Net</th>
                        <th class="text-right">OT</th>
                        <th class="text-left" style="padding-left: 6px;">s</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($chunkDays as $day)
                        <tr>
                            <td class="text-left">{{ $day['date_formatted'] }}</td>
                            <td class="text-center">{{ $day['day_short'] }}</td>
                            <td class="text-center">{{ $day['shift_id'] }}</td>

                            @if($day['shift_id'] === 'RD')
                                <!-- Rest Day: punches and metrics are empty -->
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-center"></td>
                                <td class="text-right"></td>
                                <td class="text-right"></td>
                                <td class="text-right"></td>
                                <td class="text-right"></td>
                                <td class="text-right"></td>
                                <td class="text-right"></td>
                                <td class="text-left" style="padding-left: 6px;"></td>
                            @else
                                <!-- 6 Actual Time Entry Punches -->
                                <td class="text-center">{{ $day['in_1'] ?: '' }}</td>
                                <td class="text-center">{{ $day['out_1'] ?: '' }}</td>
                                <td class="text-center">{{ $day['in_2'] ?: '' }}</td>
                                <td class="text-center">{{ $day['out_2'] ?: '' }}</td>
                                <td class="text-center">{{ $day['in_3'] ?: '' }}</td>
                                <td class="text-center">{{ $day['out_3'] ?: '' }}</td>

                                <!-- Calculated Metrics -->
                                <td class="text-right">{{ $day['reg_hrs'] !== null && $day['reg_hrs'] !== '' ? number_format((float)$day['reg_hrs'], 2) : '' }}</td>
                                <td class="text-right">{{ $day['late_mins'] > 0 ? $day['late_mins'] : '' }}</td>
                                <td class="text-right">{{ $day['ut_mins'] > 0 ? $day['ut_mins'] : '' }}</td>
                                <td class="text-right">{{ $day['absence'] > 0 ? number_format((float)$day['absence'], 2) : '' }}</td>
                                <td class="text-right">{{ $day['net'] !== null && $day['net'] !== '' ? number_format((float)$day['net'], 2) : '' }}</td>
                                <td class="text-right">{{ $day['ot'] > 0 ? number_format((float)$day['ot'], 2) : '' }}</td>
                                <td class="text-left" style="padding-left: 6px;">{{ $day['remarks'] }}</td>
                            @endif
                        </tr>
                    @endforeach

                    <!-- Totals Row with Solid Horizontal Divider under Summary Columns -->
                    <tr class="totals-row">
                        <td colspan="9" style="border: none;"></td>
                        <td class="text-right totals-divider">{{ number_format((float)$chunkRegHrs, 2) }}</td>
                        <td class="text-right totals-divider">{{ $chunkLateMins }}</td>
                        <td class="text-right totals-divider">{{ $chunkUtMins }}</td>
                        <td class="text-right totals-divider">{{ number_format((float)$chunkAbsence, 2) }}</td>
                        <td class="text-right totals-divider">{{ number_format((float)$chunkNet, 2) }}</td>
                        <td class="text-right totals-divider">{{ number_format((float)$chunkOt, 2) }}</td>
                        <td style="border: none;"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach
@endforeach

</body>
</html>
