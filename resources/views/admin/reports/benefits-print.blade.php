<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Benefits Expense Report</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        * { box-sizing: border-box; }
        body { color: #172033; font: 11px Arial, sans-serif; margin: 0; }
        .toolbar { margin-bottom: 16px; text-align: right; }
        button { background: #214676; border: 0; border-radius: 5px; color: white; cursor: pointer; padding: 10px 15px; }
        h1 { font-size: 19px; margin: 0 0 5px; }
        .meta { color: #536178; margin-bottom: 15px; }
        .summary { border: 1px solid #aab5c4; background: #f1f4f8; margin-bottom: 15px; padding: 11px; }
        .summary strong { font-size: 17px; }
        table { border-collapse: collapse; width: 100%; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; }
        th, td { border: 1px solid #aeb8c7; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #e8edf4; text-transform: uppercase; }
        .amount { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .total td { border-top: 2px solid #172033; font-weight: bold; }
        .total .amount { border-bottom: 3px double #172033; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Print / Save as PDF</button></div>
    <h1>{{ \App\Models\Setting::value('application_name', config('app.name')) }} — Benefits Expense Report</h1>
    <div class="meta">Paid expenses · {{ $filters['year'] ?? 'All years' }} · Generated {{ now()->format('d M Y, H:i') }}</div>
    <div class="summary">{{ number_format($expenses->count()) }} paid transaction{{ $expenses->count() === 1 ? '' : 's' }} &nbsp;|&nbsp; Total paid: <strong>GHS {{ number_format($totalPaid, 2) }}</strong></div>
    <table>
        <thead><tr><th>Payment Date</th><th>Staff ID</th><th>Staff</th><th>Benefit Type</th><th class="amount">Amount (GHS)</th></tr></thead>
        <tbody>
            @forelse($expenses as $expense)
                <tr><td>{{ $expense->expense_date ?: '—' }}</td><td>{{ $expense->staff_code }}</td><td>{{ $expense->staff_name }}</td><td>{{ $expense->benefit_type }}</td><td class="amount">{{ number_format((float) $expense->amount, 2) }}</td></tr>
            @empty
                <tr><td colspan="5" style="text-align:center">No paid benefit expenses match these filters.</td></tr>
            @endforelse
        </tbody>
        <tfoot><tr class="total"><td colspan="4" style="text-align:right">TOTAL PAID</td><td class="amount">GHS {{ number_format($totalPaid, 2) }}</td></tr></tfoot>
    </table>
    <script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
