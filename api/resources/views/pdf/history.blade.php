<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event History PDF</title>
    <style>
        body { font-family: sans-serif; color: #333; }
        h2 { text-align: center; color: #8A2BE2; }
        p { text-align: center; font-size: 14px; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; font-size: 13px; }
        th { background-color: #f8f9fa; text-transform: uppercase; }
        .type-fall { color: #d32f2f; font-weight: bold; }
        .type-sos { color: #1976d2; font-weight: bold; }
    </style>
</head>
<body>

    <h2>Smart Fall Detection System</h2>
    <p>
        Event History Report<br>
        Filter: <strong>{{ strtoupper(str_replace('_', ' ', $filter)) }}</strong><br>
        Date Generated: {{ date('Y-m-d H:i:s') }}
    </p>

    <table>
    <thead>
        <tr>
            <th>Date & Time</th>
            <th>Type</th>
            <th>Impact (G)</th>
            <th>Status</th>
            <th>Notes / Action Taken</th> </tr>
    </thead>
    <tbody>
        @foreach($events as $event)
        <tr>
            <td>{{ $event->occurred_at->format('M d, Y - H:i:s') }}</td>
            <td class="{{ $event->type == 'auto_fall' ? 'type-fall' : 'type-sos' }}">
                {{ $event->type == 'auto_fall' ? 'FALL' : 'SOS' }}
            </td>
            <td>{{ $event->acceleration_peak ? number_format($event->acceleration_peak, 2) . ' G' : '-' }}</td>
            <td>{{ strtoupper(str_replace('_', ' ', $event->status)) }}</td>
            <td style="font-size: 10px; font-style: italic;">
                {{ $event->notes ?? '-' }} </td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>