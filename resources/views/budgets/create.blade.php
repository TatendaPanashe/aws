@extends('layouts.main')

@section('title')
Enter Monthly Site Budgets
@endsection

@section('content')
@include('includes.header')
@include('includes.sidebar')

@php
    $siteCount = $sites->count();
    $actualUsdTotal = $actualCollections->sum(fn ($row) => (float) $row->actual_usd);
    $actualZwgTotal = $actualCollections->sum(fn ($row) => (float) $row->actual_zwg);
    $existingBudgetCount = $existingBudgets->count();
@endphp

<div class="pagetitle">
    <h1>Monthly Site Budget Entry</h1>
    <p>Choose a month and year, load the sites in scope, then enter or update targets for each site in one submission.</p>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Load Budgeting Scope</h5>
                <div class="muted">Select the period and optional network before entering site-level targets.</div>
            </div>
            <a href="{{ route('budgets.index', ['year' => $year, 'month' => $month, 'network_id' => $networkId]) }}" class="btn btn-secondary">
                <i class="bi bi-bar-chart-steps"></i> Review Comparison
            </a>
        </div>

        <div class="glass-note mb-3">
            This screen is designed for supervisors to enter the monthly budget amount for each site. Existing values for the selected month are preloaded when available.
        </div>

        <form method="GET" action="{{ route('budgets.create') }}" class="row g-3">
            <div class="col-md-4">
                <label for="year" class="form-label">Year</label>
                <select id="year" name="year" class="form-select">
                    @for ($y = Carbon\Carbon::now()->year - 2; $y <= Carbon\Carbon::now()->year + 5; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-4">
                <label for="month" class="form-label">Month</label>
                <select id="month" name="month" class="form-select">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ Carbon\Carbon::create(null, $m, 1)->format('F') }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-md-4">
                <label for="network_id" class="form-label">Network</label>
                <select id="network_id" name="network_id" class="form-select">
                    <option value="">All Networks</option>
                    @foreach ($networks as $network)
                        <option value="{{ $network->id }}" {{ (string) $networkId === (string) $network->id ? 'selected' : '' }}>
                            {{ $network->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">Load Sites</button>
                <a href="{{ route('budgets.create') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<section class="metric-grid mb-4">
    <article class="metric-card">
        <span class="metric-label"><i class="bi bi-building"></i> Sites Loaded</span>
        <strong class="metric-value">{{ number_format($siteCount) }}</strong>
        <div class="metric-note">Sites available for the selected budget entry window.</div>
    </article>
    <article class="metric-card">
        <span class="metric-label"><i class="bi bi-archive"></i> Existing Site Budgets</span>
        <strong class="metric-value">{{ number_format($existingBudgetCount) }}</strong>
        <div class="metric-note">Targets already saved for this month and year.</div>
    </article>
    <article class="metric-card">
        <span class="metric-label"><i class="bi bi-cash-stack"></i> Current Actual USD</span>
        <strong class="metric-value">${{ number_format($actualUsdTotal, 2) }}</strong>
        <div class="metric-note">Actual USD collection totals already recorded for this period.</div>
    </article>
    <article class="metric-card">
        <span class="metric-label"><i class="bi bi-wallet2"></i> Current Actual ZWG</span>
        <strong class="metric-value">ZWG {{ number_format($actualZwgTotal, 2) }}</strong>
        <div class="metric-note">Actual ZWG collection totals already recorded for this period.</div>
    </article>
</section>

@if ($sites->isEmpty())
    <div class="empty-state">
        No sites are available in the selected scope. Choose a different network or create sites first.
    </div>
@else
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Site Budget Register</h5>
                    <div class="muted">{{ Carbon\Carbon::create($year, $month, 1)->format('F Y') }} targets by site.</div>
                </div>
                <span class="soft-chip"><i class="bi bi-pencil-square"></i> Bulk entry</span>
            </div>

            <form action="{{ route('budgets.store') }}" method="POST">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="network_id" value="{{ $networkId }}">

                <div class="table-responsive table-shell">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Site</th>
                                <th>Network</th>
                                <th>Actual USD</th>
                                <th>Actual ZWG</th>
                                <th>Budget USD</th>
                                <th>Budget ZWG</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sites as $site)
                                @php
                                    $existingBudget = $existingBudgets->get((string) $site->id);
                                    $actuals = $actualCollections->get((string) $site->id);
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $site->site_name }}</strong>
                                        <input type="hidden" name="budgets[{{ $site->id }}][site_id]" value="{{ $site->id }}">
                                    </td>
                                    <td>{{ optional($site->network)->name ?? 'Unassigned' }}</td>
                                    <td>${{ number_format((float) ($actuals->actual_usd ?? 0), 2) }}</td>
                                    <td>ZWG {{ number_format((float) ($actuals->actual_zwg ?? 0), 2) }}</td>
                                    <td>
                                        <input
                                            type="number"
                                            step="0.01"
                                            class="form-control @error('budgets.' . $site->id . '.budgeted_amount_usd') is-invalid @enderror"
                                            name="budgets[{{ $site->id }}][budgeted_amount_usd]"
                                            value="{{ old('budgets.' . $site->id . '.budgeted_amount_usd', $existingBudget->budgeted_amount_usd ?? 0) }}"
                                        >
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            step="0.01"
                                            class="form-control @error('budgets.' . $site->id . '.budgeted_amount_zwg') is-invalid @enderror"
                                            name="budgets[{{ $site->id }}][budgeted_amount_zwg]"
                                            value="{{ old('budgets.' . $site->id . '.budgeted_amount_zwg', $existingBudget->budgeted_amount_zwg ?? 0) }}"
                                        >
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex gap-2 flex-wrap mt-3">
                    <button type="submit" class="btn btn-primary">Save Monthly Site Budgets</button>
                    <a href="{{ route('budgets.index', ['year' => $year, 'month' => $month, 'network_id' => $networkId]) }}" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
