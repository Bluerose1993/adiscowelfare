<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Staff Locker Register</title>
    <style>
        @page { size: A4 portrait; margin: 14mm; }
        * { box-sizing: border-box; }
        body { color: #172033; font-family: Arial, sans-serif; font-size: 11px; margin: 0; }
        .toolbar { display: flex; justify-content: flex-end; margin-bottom: 18px; }
        .toolbar button { background: #c62828; border: 0; border-radius: 6px; color: white; cursor: pointer; font-weight: 700; padding: 10px 16px; }
        header { border-bottom: 2px solid #172033; margin-bottom: 16px; padding-bottom: 10px; text-align: center; }
        h1 { font-size: 20px; margin: 0 0 5px; }
        header p { color: #586174; margin: 2px 0; }
        table { border-collapse: collapse; width: 100%; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; }
        th, td { border: 1px solid #aeb5c0; padding: 7px; text-align: left; }
        th { background: #e9edf3; font-size: 10px; text-transform: uppercase; }
        .number { text-align: center; width: 38px; }
        .locker { font-weight: 700; width: 115px; }
        .empty { color: #9b1c1c; font-weight: 400; }
        footer { color: #687083; font-size: 9px; margin-top: 10px; text-align: right; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Print / Save as PDF</button></div>
    <header>
        <h1>{{ \App\Models\Setting::value('application_name', config('app.name')) }} — Staff Locker Register</h1>
        <p>{{ $staff->count() }} staff record{{ $staff->count() === 1 ? '' : 's' }}</p>
        @if($search !== '')<p>Filtered by: {{ $search }}</p>@endif
        <p>Locker status: {{ ['all' => 'All staff', 'assigned' => 'With locker numbers', 'unassigned' => 'Without locker numbers'][$assignment] }}</p>
        <p>Generated {{ now()->format('d M Y, h:i A') }}</p>
    </header>
    <table>
        <thead><tr><th class="number">#</th><th>Staff ID</th><th>Staff Name</th><th>Department</th><th class="locker">Locker Number</th></tr></thead>
        <tbody>
        @forelse($staff as $member)
            <tr><td class="number">{{ $loop->iteration }}</td><td>{{ $member->staff_id }}</td><td>{{ $member->full_name }}</td><td>{{ $member->department ?: '—' }}</td><td class="locker {{ $member->locker_number ? '' : 'empty' }}">{{ $member->locker_number ?: 'Not assigned' }}</td></tr>
        @empty
            <tr><td colspan="5" style="text-align:center">No staff records found.</td></tr>
        @endforelse
        </tbody>
    </table>
    <footer>Staff Locker Register</footer>
    <script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
