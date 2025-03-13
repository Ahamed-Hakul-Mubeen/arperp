@php
    use App\Models\Utility;
    $setting = \App\Models\Utility::settings();
    $logo = \App\Models\Utility::get_file('uploads/logo/');

    $company_logo = $setting['company_logo_dark'] ?? '';
    $company_logos = $setting['company_logo_light'] ?? '';
    $company_small_logo = $setting['company_small_logo'] ?? '';

@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <title>Attendance Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header img {
            width: 100px;
            height: auto;
            margin-bottom: 10px;
        }

        .header h1 {
            font-size: 24px;
            margin: 0;
            color: #444;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #f7f7f7;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                margin: 0;
                padding: 0;
            }
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            color: #888;
            font-size: 12px;
        }

        button {
            margin: 10px;
            padding: 8px 12px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            background-color: #007bff;
            color: white;
            border-radius: 5px;
        }

        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<div class="header">
    <img src="{{ $logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-light.png') }}"
    alt="{{ config('app.name', 'TZI-SaaS') }}" class="logo logo-lg">
    <h1>Attendance Report</h1>
</div>

<table>
    <thead>
        <tr>
            <th>Employee</th>
            <th>Date</th>
            <th>Status</th>
            <th>Clock In</th>
            <th>Clock Out</th>
            <th>Late</th>
            <th>Early Leaving</th>
            <th>Overtime</th>
            <th>Break Time</th>
        </tr>
    </thead>
    <tbody>
        @foreach($attendanceEmployee as $attendance)
        <tr>
            <td>{{ $attendance->employee->name }}</td>
            <td>{{ $attendance->date }}</td>
            <td>{{ $attendance->status }}</td>
            <td>{{ $attendance->clock_in }}</td>
            <td>{{ $attendance->clock_out }}</td>
            <td>{{ $attendance->late }}</td>
            <td>{{ $attendance->early_leaving }}</td>
            <td>{{ $attendance->overtime }}</td>
            <td>{{ $attendance->total_break_duration }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="no-print footer">
    <button onclick="window.print()"> <i class="fa fa-print"></i> Print</button>
    <button onclick="window.close()"> <i class="fa fa-close"></i> Close</button>
</div>

</body>
</html>