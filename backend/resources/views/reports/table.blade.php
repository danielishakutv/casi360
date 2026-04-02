<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #1a202c; }

        .header { padding: 16px 0; border-bottom: 2px solid #2d3748; margin-bottom: 12px; }
        .header h1 { font-size: 16px; color: #2d3748; margin-bottom: 4px; }
        .header .subtitle { font-size: 9px; color: #718096; }

        .meta-table { margin-bottom: 14px; }
        .meta-table td { padding: 2px 10px 2px 0; font-size: 9px; color: #4a5568; }
        .meta-table td.label { font-weight: bold; color: #2d3748; }

        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data thead th {
            background: #edf2f7;
            color: #2d3748;
            font-size: 9px;
            font-weight: 600;
            text-align: left;
            padding: 6px 8px;
            border-bottom: 2px solid #cbd5e0;
        }
        table.data tbody td {
            padding: 5px 8px;
            font-size: 9px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
            word-wrap: break-word;
        }
        table.data tbody tr:nth-child(even) { background: #f7fafc; }

        .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 8px; color: #a0aec0; text-align: center; }

        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <div class="subtitle">CASI 360 &mdash; Generated {{ now()->format('d M Y, H:i') }}</div>
    </div>

    @if(!empty($meta))
    <table class="meta-table">
        @foreach($meta as $key => $value)
        <tr>
            <td class="label">{{ $key }}:</td>
            <td>{{ $value }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    <table class="data">
        <thead>
            <tr>
                @foreach($headers as $header)
                <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                @foreach(array_values((array) $row) as $cell)
                <td>{{ $cell }}</td>
                @endforeach
            </tr>
            @empty
            <tr>
                <td colspan="{{ count($headers) }}" style="text-align:center; padding:20px; color:#a0aec0;">No records found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        {{ $title }} &bull; {{ $rows instanceof \Illuminate\Support\Collection ? $rows->count() : count($rows) }} record(s) &bull; CASI 360
    </div>
</body>
</html>
