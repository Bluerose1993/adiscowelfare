@extends('layouts.app', ['title' => 'Record Dues Payment'])

@section('content')
<div class="row">
    <div class="col-lg-5">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Find Staff</h3></div>
            <div class="card-body">
                <div class="form-group position-relative">
                    <label class="required">Search by name, Staff ID, or phone</label>
                    <input id="staffSearch" class="form-control form-control-lg" autocomplete="off" placeholder="Start typing..." value="{{ old('staff_search', $selectedStaff?->full_name) }}">
                    <div id="staffSearchResults" class="staff-search-results d-none"></div>
                </div>
                <div id="staffSummary" class="text-muted">Select a staff member to see dues and benefit status.</div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <form method="post" action="{{ route('admin.dues.store') }}" data-prevent-double-submit="true">
            @csrf
            <input type="hidden" id="selectedStaffId" name="staff_id" value="{{ old('staff_id', $selectedStaff?->id ?? session('selected_staff_id')) }}">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Payment Details</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 form-group"><label>Year</label><input id="paymentYear" name="payment_year" type="number" min="2000" max="2100" class="form-control" value="{{ old('payment_year', $year) }}" required></div>
                        <div class="col-md-4 form-group"><label>Month</label><select id="paymentMonth" name="payment_month" class="form-control" required>@foreach($months as $number => $name)<option value="{{ $number }}" @selected(old('payment_month', now()->month) == $number)>{{ $name }}</option>@endforeach</select></div>
                        <div class="col-md-5 form-group"><label>Amount</label><input id="paymentAmount" name="amount" type="number" step="0.01" min="0.01" class="form-control" value="{{ old('amount') }}" required><small class="form-text text-muted">Any excess automatically rolls into the following unpaid months.</small></div>
                        <div class="col-md-4 form-group"><label>Payment Date</label><input name="payment_date" type="date" class="form-control" value="{{ old('payment_date', now()->toDateString()) }}" required></div>
                        <div class="col-md-4 form-group"><label>Method</label><input name="payment_method" class="form-control" value="{{ old('payment_method') }}"></div>
                        <div class="col-md-4 form-group"><label>Reference</label><input name="reference_number" class="form-control" value="{{ old('reference_number') }}"></div>
                        <div class="col-12 form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea></div>
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-success"><i class="fas fa-save"></i> Save Payment</button></div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const search = document.getElementById('staffSearch');
    const results = document.getElementById('staffSearchResults');
    const selected = document.getElementById('selectedStaffId');
    const summary = document.getElementById('staffSummary');
    const year = document.getElementById('paymentYear');
    const month = document.getElementById('paymentMonth');
    const amount = document.getElementById('paymentAmount');
    let timer;

    function renderSummary(data) {
        const rows = Object.entries(data.monthly).map(([number, row]) => {
            const paid = ['paid', 'overpaid'].includes(row.status);
            const state = paid ? 'paid' : 'unpaid';
            return `<button type="button" class="month-tile month-payment-tile month-state-${state}" data-month="${number}" data-balance="${Number(row.balance)}" data-expected="${Number(row.expected)}"><span class="month-check"><i class="fas fa-${paid ? 'check' : 'exclamation'}"></i></span><strong>${row.month.slice(0,3)}</strong><div>${Number(row.paid).toFixed(2)} / ${Number(row.expected).toFixed(2)}</div><span class="month-status">${row.status.replace('_',' ')}</span></button>`;
        }).join('');
        summary.innerHTML = `<h5>${data.staff.full_name}</h5><p class="mb-1">Staff ID: ${data.staff.staff_id || 'Unverified'} | Year total: ${Number(data.year_total).toFixed(2)}</p><p class="mb-2">Benefits received: ${Number(data.benefits_received).toFixed(2)} | Pending benefits: ${Number(data.pending_benefits).toFixed(2)}</p><div class="month-grid">${rows}</div>`;
    }

    async function loadSummary(id) {
        const response = await fetch(`{{ url('/admin/dues/staff') }}/${id}/summary?year=${year.value}`);
        renderSummary(await response.json());
    }

    search.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(async () => {
            const q = search.value.trim();
            if (q.length < 2) { results.classList.add('d-none'); return; }
            const response = await fetch(`{{ route('admin.dues.search') }}?q=${encodeURIComponent(q)}&year=${year.value}`);
            const people = await response.json();
            results.innerHTML = people.map(person => `<button type="button" class="list-group-item list-group-item-action" data-id="${person.id}" data-name="${person.full_name}"><strong>${person.full_name}</strong><br><small>${person.staff_id || 'No Staff ID'} | ${person.phone || 'No phone'} | ${person.department || ''} | Year: ${Number(person.year_total).toFixed(2)}</small></button>`).join('');
            results.classList.toggle('d-none', people.length === 0);
        }, 250);
    });

    results.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-id]');
        if (!button) return;
        selected.value = button.dataset.id;
        search.value = button.dataset.name;
        results.classList.add('d-none');
        loadSummary(button.dataset.id);
    });

    summary.addEventListener('click', (event) => {
        const tile = event.target.closest('.month-payment-tile');
        if (!tile) return;
        month.value = tile.dataset.month;
        const suggested = Number(tile.dataset.balance) > 0 ? Number(tile.dataset.balance) : Number(tile.dataset.expected);
        amount.value = suggested > 0 ? suggested.toFixed(2) : '';
        amount.focus();
        amount.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    year.addEventListener('change', () => {
        if (selected.value) loadSummary(selected.value);
    });

    if (selected.value) loadSummary(selected.value);
});
</script>
@endpush
