<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Benefit Request #{{ $requestRecord->id }}</title>
    <style>
        @page { size: A4 portrait; margin: 16mm; }
        body { color: #172033; font: 12px Arial, sans-serif; margin: 0; }
        .toolbar { text-align: right; margin-bottom: 22px; }
        button { background: #243f77; border: 0; border-radius: 5px; color: #fff; cursor: pointer; padding: 10px 16px; }
        header { border-bottom: 2px solid #243f77; padding-bottom: 12px; margin-bottom: 20px; }
        h1 { font-size: 21px; margin: 0 0 5px; }
        h2 { font-size: 14px; margin: 20px 0 8px; }
        .muted { color: #59667b; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #c9d1dc; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #eef2f8; width: 34%; }
        .description { border: 1px solid #c9d1dc; padding: 12px; white-space: pre-wrap; }
        .note { color: #59667b; font-size: 10px; margin-top: 20px; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Print / Save as PDF</button></div>
    <header>
        <h1>{{ \App\Models\Setting::value('application_name', config('app.name')) }}</h1>
        <div>Benefit Request #{{ $requestRecord->id }}</div>
        <div class="muted">Generated {{ now()->format('d M Y, H:i') }}</div>
    </header>
    <table>
        <tr><th>Staff</th><td>{{ $requestRecord->staff?->full_name }} ({{ $requestRecord->staff?->staff_id }})</td></tr>
        <tr><th>Benefit type</th><td>{{ $requestRecord->benefitType?->name }}</td></tr>
        <tr><th>Request</th><td>{{ $requestRecord->subject }}</td></tr>
        <tr><th>Submitted</th><td>{{ $requestRecord->submitted_at?->format('d M Y, H:i') ?: '—' }}</td></tr>
        <tr><th>Review status</th><td>{{ ucwords(str_replace('_', ' ', $requestRecord->status)) }}</td></tr>
        <tr><th>Requested amount</th><td>GHS {{ number_format($requestRecord->requested_amount ?? 0, 2) }}</td></tr>
        <tr><th>Approved amount</th><td>{{ $requestRecord->approved_amount !== null ? 'GHS '.number_format($requestRecord->approved_amount, 2) : '—' }}</td></tr>
        <tr><th>Approved by</th><td>{{ $requestRecord->approved_at ? ($requestRecord->reviewer?->name ?: 'Administrator') : '—' }}</td></tr>
        <tr><th>Approval date and time</th><td>{{ $requestRecord->approved_at?->format('d M Y, H:i:s') ?: 'Not approved' }}</td></tr>
        <tr><th>Amount staff confirmed received</th><td>{{ $requestRecord->receipt_confirmed_at ? 'GHS '.number_format($requestRecord->received_amount, 2) : 'Awaiting staff confirmation' }}</td></tr>
        <tr><th>Receipt confirmed at</th><td>{{ $requestRecord->receipt_confirmed_at?->format('d M Y, H:i:s') ?: '—' }}</td></tr>
    </table>
    <h2>Description</h2><div class="description">{{ $requestRecord->description }}</div>
    @if($requestRecord->review_notes)<h2>Review notes</h2><div class="description">{{ $requestRecord->review_notes }}</div>@endif
    <p class="note">This record reflects the current request, administrator approval, and any confirmation submitted by the staff member.</p>
    <script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
