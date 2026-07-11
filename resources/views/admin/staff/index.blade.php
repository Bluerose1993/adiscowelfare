@extends('layouts.app', ['title' => 'All Staff'])

@section('actions')
<a href="{{ route('admin.staff.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Add Staff</a>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <form method="get" id="staffSearchForm" class="form-inline">
            <div class="staff-live-search"><i class="fas fa-search"></i><input id="staffSearchInput" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search name, staff ID, phone" autocomplete="off"><span id="staffSearchSpinner" class="staff-search-spinner d-none"><i class="fas fa-circle-notch fa-spin"></i></span></div>
            <button class="btn btn-outline-primary"><i class="fas fa-search"></i> Search</button>
        </form>
    </div>
    <div class="card-body"><div id="staffResults" aria-live="polite">@include('admin.staff.partials.results', ['staff' => $staff])</div></div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('staffSearchForm');
    const input = document.getElementById('staffSearchInput');
    const results = document.getElementById('staffResults');
    const spinner = document.getElementById('staffSearchSpinner');
    let timer;
    let controller;

    async function loadResults(url, updateHistory = true) {
        if (controller) controller.abort();
        controller = new AbortController();
        spinner.classList.remove('d-none');
        results.classList.add('is-loading');
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal });
            if (!response.ok) throw new Error('Search failed');
            const data = await response.json();
            results.innerHTML = data.html;
            if (updateHistory) history.replaceState({}, '', url);
        } catch (error) {
            if (error.name !== 'AbortError') results.innerHTML = '<div class="alert alert-danger">The staff search could not be completed. Please try again.</div>';
        } finally {
            spinner.classList.add('d-none');
            results.classList.remove('is-loading');
        }
    }

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const url = new URL(form.action || window.location.href, window.location.origin);
            url.searchParams.set('search', input.value.trim());
            url.searchParams.delete('page');
            loadResults(url.toString());
        }, 250);
    });

    form.addEventListener('submit', event => {
        event.preventDefault();
        const url = new URL(form.action || window.location.href, window.location.origin);
        url.searchParams.set('search', input.value.trim());
        url.searchParams.delete('page');
        loadResults(url.toString());
    });

    results.addEventListener('click', event => {
        const link = event.target.closest('.pagination a');
        if (!link) return;
        event.preventDefault();
        loadResults(link.href);
    });
});
</script>
@endpush
