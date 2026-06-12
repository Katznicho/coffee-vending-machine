<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; }
        h1 { font-size: 18px; margin: 0 0 8px 0; }
        .meta { margin: 0 0 14px 0; color: #475569; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background: #f1f5f9; font-weight: 700; }
    </style>
</head>
<body>
    <h1>{{ $reportTitle }}</h1>
    <p class="meta">
        Generated: {{ now()->format('Y-m-d H:i') }}
        @if(!empty($filters['from']) || !empty($filters['to']))
            | Period: {{ $filters['from'] ?? 'Any' }} to {{ $filters['to'] ?? 'Any' }}
        @endif
    </p>

    <table>
        <thead>
            <tr>
                @foreach($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['branch'] }}</td>
                    <td>{{ $row['attendant'] }}</td>
                    <td>{{ $row['product'] }}</td>
                    <td>{{ number_format((float) $row['liters'], 1) }}</td>
                    <td>{{ $row['event'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No records found for selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
