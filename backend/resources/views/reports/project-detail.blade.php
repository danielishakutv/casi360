<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $project['name'] }} &mdash; Full Project Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #1a202c; }

        .header { padding: 16px 0; border-bottom: 2px solid #2d3748; margin-bottom: 14px; }
        .header h1 { font-size: 18px; color: #2d3748; margin-bottom: 2px; }
        .header .code { font-size: 11px; color: #718096; }
        .header .subtitle { font-size: 9px; color: #a0aec0; margin-top: 4px; }

        h2 { font-size: 13px; color: #2d3748; padding: 10px 0 6px; border-bottom: 1px solid #e2e8f0; margin-top: 18px; }

        .info-grid { display: table; width: 100%; margin-bottom: 10px; }
        .info-row { display: table-row; }
        .info-row .label { display: table-cell; width: 150px; padding: 3px 8px 3px 0; font-weight: bold; color: #4a5568; font-size: 10px; }
        .info-row .value { display: table-cell; padding: 3px 0; font-size: 10px; color: #1a202c; }

        table.data { width: 100%; border-collapse: collapse; margin: 8px 0 14px; }
        table.data thead th { background: #edf2f7; color: #2d3748; font-size: 9px; font-weight: 600; text-align: left; padding: 6px 8px; border-bottom: 2px solid #cbd5e0; }
        table.data tbody td { padding: 5px 8px; font-size: 9px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.data tbody tr:nth-child(even) { background: #f7fafc; }

        .summary-box { background: #edf2f7; padding: 10px 14px; margin: 8px 0; border-radius: 4px; }
        .summary-box .item { display: inline-block; margin-right: 24px; }
        .summary-box .item .label { font-size: 8px; color: #718096; text-transform: uppercase; }
        .summary-box .item .value { font-size: 12px; font-weight: bold; color: #2d3748; }

        .footer { margin-top: 24px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 8px; color: #a0aec0; text-align: center; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    {{-- Cover / Basic Info --}}
    <div class="header">
        <h1>{{ $project['name'] }}</h1>
        <div class="code">{{ $project['project_code'] }}</div>
        <div class="subtitle">CASI 360 &mdash; Full Project Report &mdash; Generated {{ now()->format('d M Y, H:i') }}</div>
    </div>

    <div class="info-grid">
        <div class="info-row"><span class="label">Department</span><span class="value">{{ $project['department'] }}</span></div>
        <div class="info-row"><span class="label">Project Manager</span><span class="value">{{ $project['project_manager'] }}</span></div>
        <div class="info-row"><span class="label">Start Date</span><span class="value">{{ $project['start_date'] }}</span></div>
        <div class="info-row"><span class="label">End Date</span><span class="value">{{ $project['end_date'] }}</span></div>
        <div class="info-row"><span class="label">Status</span><span class="value">{{ $project['status'] }}</span></div>
        <div class="info-row"><span class="label">Location</span><span class="value">{{ $project['location'] }}</span></div>
    </div>

    <div class="summary-box">
        <span class="item"><span class="label">Total Budget</span><br><span class="value">{{ $project['currency'] }} {{ number_format($project['total_budget'], 2) }}</span></span>
        <span class="item"><span class="label">Activities</span><br><span class="value">{{ count($activities) }}</span></span>
        <span class="item"><span class="label">Team Members</span><br><span class="value">{{ count($team) }}</span></span>
        <span class="item"><span class="label">Donors</span><br><span class="value">{{ count($donors) }}</span></span>
    </div>

    @if($project['description'])
    <h2>Description</h2>
    <p style="font-size:10px; line-height:1.6; padding:6px 0;">{{ $project['description'] }}</p>
    @endif

    @if($project['objectives'])
    <h2>Objectives</h2>
    <p style="font-size:10px; line-height:1.6; padding:6px 0;">{{ $project['objectives'] }}</p>
    @endif

    {{-- Donors --}}
    @if(count($donors) > 0)
    <h2>Donors ({{ count($donors) }})</h2>
    <table class="data">
        <thead><tr><th>Name</th><th>Type</th><th>Email</th><th>Contribution</th></tr></thead>
        <tbody>
            @foreach($donors as $d)
            <tr><td>{{ $d['name'] }}</td><td>{{ $d['type'] }}</td><td>{{ $d['email'] }}</td><td>{{ number_format($d['contribution_amount'], 2) }}</td></tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Partners --}}
    @if(count($partners) > 0)
    <h2>Partners ({{ count($partners) }})</h2>
    <table class="data">
        <thead><tr><th>Name</th><th>Role</th><th>Contact Person</th><th>Email</th></tr></thead>
        <tbody>
            @foreach($partners as $p)
            <tr><td>{{ $p['name'] }}</td><td>{{ $p['role'] }}</td><td>{{ $p['contact_person'] }}</td><td>{{ $p['email'] }}</td></tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Team --}}
    @if(count($team) > 0)
    <h2>Team Members ({{ count($team) }})</h2>
    <table class="data">
        <thead><tr><th>Name</th><th>Role</th><th>Start Date</th><th>End Date</th></tr></thead>
        <tbody>
            @foreach($team as $t)
            <tr><td>{{ $t['employee_name'] }}</td><td>{{ $t['role'] }}</td><td>{{ $t['start_date'] }}</td><td>{{ $t['end_date'] }}</td></tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Activities --}}
    @if(count($activities) > 0)
    <h2>Activities / Milestones ({{ count($activities) }})</h2>
    <table class="data">
        <thead><tr><th>Title</th><th>Start</th><th>End</th><th>Target</th><th>Status</th><th>% Complete</th></tr></thead>
        <tbody>
            @foreach($activities as $a)
            <tr><td>{{ $a['title'] }}</td><td>{{ $a['start_date'] }}</td><td>{{ $a['end_date'] }}</td><td>{{ $a['target_date'] }}</td><td>{{ $a['status'] }}</td><td>{{ $a['completion_percentage'] }}%</td></tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Budget Lines --}}
    @if(count($budgetLines) > 0)
    <h2>Budget Lines ({{ count($budgetLines) }})</h2>
    <table class="data">
        <thead><tr><th>Category</th><th>Description</th><th>Unit</th><th>Qty</th><th>Unit Cost</th><th>Total Cost</th></tr></thead>
        <tbody>
            @php $budgetTotal = 0; @endphp
            @foreach($budgetLines as $b)
            @php $budgetTotal += $b['total_cost']; @endphp
            <tr><td>{{ $b['category'] }}</td><td>{{ $b['description'] }}</td><td>{{ $b['unit'] }}</td><td>{{ $b['quantity'] }}</td><td>{{ number_format($b['unit_cost'], 2) }}</td><td>{{ number_format($b['total_cost'], 2) }}</td></tr>
            @endforeach
            <tr style="font-weight:bold; background:#edf2f7;"><td colspan="5" style="text-align:right; padding:6px 8px;">TOTAL</td><td style="padding:6px 8px;">{{ number_format($budgetTotal, 2) }}</td></tr>
        </tbody>
    </table>
    @endif

    {{-- Notes --}}
    @if(count($notes) > 0)
    <h2>Project Notes ({{ count($notes) }})</h2>
    <table class="data">
        <thead><tr><th>Title</th><th>Content</th><th>Author</th><th>Date</th></tr></thead>
        <tbody>
            @foreach($notes as $n)
            <tr><td>{{ $n['title'] }}</td><td>{{ \Illuminate\Support\Str::limit($n['content'], 200) }}</td><td>{{ $n['author'] }}</td><td>{{ $n['created_at'] }}</td></tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        {{ $project['name'] }} ({{ $project['project_code'] }}) &bull; Full Project Report &bull; CASI 360
    </div>
</body>
</html>
