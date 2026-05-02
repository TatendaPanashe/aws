@extends('layouts.main')

@section('title')
Site Budget Management
@endsection

@section('content')
@include('includes.header')
@include('includes.sidebar')

<div class="pagetitle">
    <h1>Site Budget Management</h1>
    <p>Set monthly targets per site and compare them against actual collections using either a monthly or year-to-date view.</p>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Filter Monthly Budget Review</h5>
                <div class="muted">Select the year, month, view mode, and optional network to compare actual collections against site targets.</div>
            </div>
            <a href="{{ route('budgets.create', ['year' => $year, 'month' => $month, 'network_id' => $networkId]) }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Enter Site Budgets
            </a>
        </div>

        <div class="glass-note mb-3">
            Active period: <strong>{{ $periodLabel }}</strong>. Open the dedicated USD or ZWG chart page to view horizontal site-by-site budget comparisons with the same chart style in both monthly and year-to-date views.
        </div>

        <form class="row g-3" method="get" action="{{ route('budgets.index') }}">
            <div class="col-md-3">
                <label for="year" class="form-label">Year</label>
                <select id="year" class="form-select" name="year">
                    @for ($y = Carbon\Carbon::now()->year - 5; $y <= Carbon\Carbon::now()->year + 5; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <label for="month" class="form-label">Month</label>
                <select id="month" class="form-select" name="month">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ Carbon\Carbon::create(null, $m, 1)->format('F') }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <label for="view_mode" class="form-label">View</label>
                <select id="view_mode" class="form-select" name="view_mode">
                    <option value="monthly" {{ $viewMode === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="ytd" {{ $viewMode === 'ytd' ? 'selected' : '' }}>Year-to-Date</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="network_id" class="form-label">Network</label>
                <select id="network_id" class="form-select" name="network_id">
                    <option value="">All Networks</option>
                    @foreach ($networks as $network)
                        <option value="{{ $network->id }}" {{ (string) $networkId === (string) $network->id ? 'selected' : '' }}>
                            {{ $network->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="{{ route('budgets.index') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<section class="metric-grid mb-4">
    @foreach($summaryCards as $card)
        <article class="metric-card">
            <span class="metric-label"><i class="{{ $card['icon'] }}"></i> {{ $card['label'] }}</span>
            <strong class="metric-value">{{ $card['value'] }}</strong>
            <div class="metric-note">{{ $card['note'] }}</div>
        </article>
    @endforeach
</section>

@if ($comparisonRows->isEmpty())
    <div class="empty-state">
        No site budgets or actual collections were found for {{ $periodLabel }} in the selected scope.
    </div>
@else
    <div class="surface-grid two-up mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h5 class="card-title mb-1">USD Comparison Page</h5>
                        <div class="muted">Open the dedicated horizontal bar chart for site budget vs actual USD collections.</div>
                    </div>
                    <span class="soft-chip"><i class="bi bi-cash-stack"></i> USD</span>
                </div>
                <div class="glass-note mb-3">
                    This view focuses only on USD values and includes a visible key for `Budget USD` and `Actual USD`.
                </div>
                <a href="{{ route('budgets.charts.usd', ['year' => $year, 'month' => $month, 'network_id' => $networkId, 'view_mode' => $viewMode]) }}" class="btn btn-primary">
                    <i class="bi bi-bar-chart-line"></i> Open USD Chart Page
                </a>
            </div>
        </div>

        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h5 class="card-title mb-1">ZWG Comparison Page</h5>
                        <div class="muted">Open the dedicated horizontal bar chart for site budget vs actual ZWG collections.</div>
                    </div>
                    <span class="soft-chip"><i class="bi bi-wallet2"></i> ZWG</span>
                </div>
                <div class="glass-note mb-3">
                    This view focuses only on ZWG values and includes a visible key for `Budget ZWG` and `Actual ZWG`.
                </div>
                <a href="{{ route('budgets.charts.zwg', ['year' => $year, 'month' => $month, 'network_id' => $networkId, 'view_mode' => $viewMode]) }}" class="btn btn-primary">
                    <i class="bi bi-bar-chart-line"></i> Open ZWG Chart Page
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Monthly Site Target Register</h5>
                    <div class="muted">Budget targets and actual results for {{ $periodLabel }}.</div>
                </div>
                <span class="soft-chip"><i class="bi bi-table"></i> Site-by-site comparison</span>
            </div>

            <div class="table-responsive table-shell">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Site</th>
                            <th>Network</th>
                            <th>Budget USD</th>
                            <th>Actual USD</th>
                            <th>Variance USD</th>
                            <th>Budget ZWG</th>
                            <th>Actual ZWG</th>
                            <th>Variance ZWG</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comparisonRows as $row)
                            <tr>
                                <td>{{ $row['site_name'] }}</td>
                                <td>{{ $row['network_name'] }}</td>
                                <td>${{ number_format($row['budgeted_amount_usd'], 2) }}</td>
                                <td>${{ number_format($row['actual_usd'], 2) }}</td>
                                <td class="{{ $row['variance_usd'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    ${{ number_format($row['variance_usd'], 2) }}
                                </td>
                                <td>ZWG {{ number_format($row['budgeted_amount_zwg'], 2) }}</td>
                                <td>ZWG {{ number_format($row['actual_zwg'], 2) }}</td>
                                <td class="{{ $row['variance_zwg'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    ZWG {{ number_format($row['variance_zwg'], 2) }}
                                </td>
                                <td>
                                    <span class="soft-chip">
                                        {{ $row['is_on_target'] ? 'On / Above Target' : 'Below Target' }}
                                    </span>
                                </td>
                                <td>
                                    @if ($row['budget_id'])
                                        <div class="d-flex gap-2 flex-wrap">
                                            <a href="{{ route('budgets.edit', $row['budget_id']) }}" class="btn btn-sm btn-info">Edit</a>
                                            <form action="{{ route('budgets.destroy', $row['budget_id']) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this site budget?')">Delete</button>
                                            </form>
                                        </div>
                                    @else
                                        <a href="{{ route('budgets.create', ['year' => $year, 'month' => $month, 'network_id' => $networkId]) }}" class="btn btn-sm btn-secondary">
                                            Add Target
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

@endsection
