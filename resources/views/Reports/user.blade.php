@extends('layouts.main')

@section('title')
User Reports
@endsection

@section('content')
@include('includes.header')
@include('includes.sidebar')

@php
    $selectedUser = $users->firstWhere('id', request('user_id'));
    $hasFilters = request()->filled(['startdate', 'enddate', 'user_id']);
    $transactionCount = $filteredTransactions->count();
    $siteCount = $filteredTransactions->pluck('siteid')->filter()->unique()->count();
    $usdTransactions = (float) $filteredTransactions->sum('insurance_transactions');
    $zwgTransactions = (float) $filteredTransactions->sum('zwg_insurance_transactions');
    $latestSubmission = $filteredTransactions->sortByDesc('created_at')->first();
    $summaryCards = [
        [
            'label' => 'Matched Sheets',
            'value' => number_format($transactionCount),
            'note' => 'Collection records returned for the selected user.',
            'icon' => 'bi bi-journal-text',
        ],
        [
            'label' => 'Sites Worked',
            'value' => number_format($siteCount),
            'note' => 'Distinct sites represented in the current report.',
            'icon' => 'bi bi-building',
        ],
        [
            'label' => 'USD Transactions',
            'value' => '$' . number_format($usdTransactions, 2),
            'note' => 'Total USD insurance transaction value for the selected user.',
            'icon' => 'bi bi-cash-stack',
        ],
        [
            'label' => 'ZWG Transactions',
            'value' => 'ZWG ' . number_format($zwgTransactions, 2),
            'note' => 'Total ZWG insurance transaction value for the selected user.',
            'icon' => 'bi bi-wallet2',
        ],
        [
            'label' => 'Average USD Sheet',
            'value' => '$' . number_format($transactionCount ? $usdTransactions / $transactionCount : 0, 2),
            'note' => 'Average USD value per returned collection record.',
            'icon' => 'bi bi-graph-up',
        ],
        [
            'label' => 'Latest Submission',
            'value' => $latestSubmission ? \Carbon\Carbon::parse($latestSubmission->created_at)->format('d M Y H:i') : 'No data',
            'note' => 'Most recent captured submission inside the selected window.',
            'icon' => 'bi bi-clock-history',
        ],
    ];
@endphp

<div class="pagetitle">
    <h1>User Reports</h1>
    <p>Track one user across time and sites, then export the detailed USD and ZWG sheets for further review.</p>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Filter User Reports</h5>
                <div class="muted">Choose a user and reporting window to inspect individual collection performance.</div>
            </div>
            <a href="{{ route('reports.hub') }}" class="btn btn-secondary">
                <i class="bi bi-grid-1x2"></i> Reports Hub
            </a>
        </div>

        <div class="glass-note mb-3">
            This report shows daily movement, site distribution, and detailed transaction rows for the selected user across USD and ZWG activity.
        </div>

        <form class="row g-3" method="post" action="{{ route('user.reports.filter') }}">
            @csrf
            <div class="col-md-4">
                <label for="startdate" class="form-label">Start Date</label>
                <input type="date" class="form-control" name="startdate" id="startdate" value="{{ request('startdate') }}">
            </div>
            <div class="col-md-4">
                <label for="enddate" class="form-label">End Date</label>
                <input type="date" class="form-control" name="enddate" id="enddate" value="{{ request('enddate') }}">
            </div>
            <div class="col-md-4">
                <label for="userId" class="form-label">Select User</label>
                <select id="userId" class="form-select" name="user_id">
                    <option value="">Choose user...</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" {{ (string) request('user_id') === (string) $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="{{ route('user.reports') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

@if ($filteredTransactions->isNotEmpty())
    <section class="metric-grid mb-4">
        @foreach($summaryCards as $card)
            <article class="metric-card">
                <span class="metric-label"><i class="{{ $card['icon'] }}"></i> {{ $card['label'] }}</span>
                <strong class="metric-value">{{ $card['value'] }}</strong>
                <div class="metric-note">{{ $card['note'] }}</div>
            </article>
        @endforeach
    </section>

    <div class="surface-grid two-up mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h5 class="card-title mb-1">Daily Transactions</h5>
                        <div class="muted">Daily USD and ZWG transaction movement for {{ $selectedUser?->name ?? 'the selected user' }}.</div>
                    </div>
                    <span class="soft-chip"><i class="bi bi-calendar-week"></i> Daily view</span>
                </div>
                <canvas id="userLineChart" style="max-height: 380px;"></canvas>
            </div>
        </div>

        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h5 class="card-title mb-1">Transactions By Site</h5>
                        <div class="muted">Which sites contributed the most transaction value for the selected user.</div>
                    </div>
                    <span class="soft-chip"><i class="bi bi-building"></i> Site spread</span>
                </div>
                <canvas id="userBarChart" style="max-height: 380px;"></canvas>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Detailed USD Transactions</h5>
                    <div class="muted">All USD records returned for {{ $selectedUser?->name ?? 'the selected user' }} in the current window.</div>
                </div>
                <button class="btn btn-primary" type="button" onclick="exportUserTable('userUsdTable', 'USD_User_Report.xlsx', 'USD User Report')">
                    <i class="bi bi-download"></i> Export USD
                </button>
            </div>

            <div class="table-responsive table-shell">
                <table class="table table-striped datatable" id="userUsdTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Site Name</th>
                            <th>Total USD Ins. Trans.</th>
                            <th>USD Third Party Premiums</th>
                            <th>USD Full Cover Premiums</th>
                            <th>USD Zinara Fees</th>
                            <th>USD Cash</th>
                            <th>USD Swipe</th>
                            <th>USD Transfers</th>
                            <th>Bank</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($filteredTransactions as $transaction)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($transaction->created_at)->format('d M Y H:i') }}</td>
                                <td>{{ $transaction->site_name ?? 'N/A' }}</td>
                                <td>${{ number_format((float) $transaction->insurance_transactions, 2) }}</td>
                                <td>${{ number_format((float) $transaction->third_party_premiums, 2) }}</td>
                                <td>${{ number_format((float) $transaction->full_cover_premiums, 2) }}</td>
                                <td>${{ number_format((float) $transaction->zinara_fees, 2) }}</td>
                                <td>${{ number_format((float) $transaction->usd_cash, 2) }}</td>
                                <td>${{ number_format((float) $transaction->usd_swipe, 2) }}</td>
                                <td>${{ number_format((float) $transaction->usd_transfers, 2) }}</td>
                                <td>{{ $transaction->bank }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2">Totals</th>
                            <th>${{ number_format((float) $filteredTransactions->sum('insurance_transactions'), 2) }}</th>
                            <th>${{ number_format((float) $filteredTransactions->sum('third_party_premiums'), 2) }}</th>
                            <th>${{ number_format((float) $filteredTransactions->sum('full_cover_premiums'), 2) }}</th>
                            <th>${{ number_format((float) $filteredTransactions->sum('zinara_fees'), 2) }}</th>
                            <th>${{ number_format((float) $filteredTransactions->sum('usd_cash'), 2) }}</th>
                            <th>${{ number_format((float) $filteredTransactions->sum('usd_swipe'), 2) }}</th>
                            <th>${{ number_format((float) $filteredTransactions->sum('usd_transfers'), 2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Detailed ZWG Transactions</h5>
                    <div class="muted">All ZWG records returned for {{ $selectedUser?->name ?? 'the selected user' }} in the current window.</div>
                </div>
                <button class="btn btn-primary" type="button" onclick="exportUserTable('userZwgTable', 'ZWG_User_Report.xlsx', 'ZWG User Report')">
                    <i class="bi bi-download"></i> Export ZWG
                </button>
            </div>

            <div class="table-responsive table-shell">
                <table class="table table-striped datatable" id="userZwgTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Site Name</th>
                            <th>Total ZWG Ins. Trans.</th>
                            <th>ZWG Third Party Premiums</th>
                            <th>ZWG Full Cover Premiums</th>
                            <th>ZWG Zinara Fees</th>
                            <th>ZWG Cash</th>
                            <th>ZWG Swipe</th>
                            <th>ZWG Transfers</th>
                            <th>Bank</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($filteredTransactions as $transaction)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($transaction->created_at)->format('d M Y H:i') }}</td>
                                <td>{{ $transaction->site_name ?? 'N/A' }}</td>
                                <td>ZWG {{ number_format((float) $transaction->zwg_insurance_transactions, 2) }}</td>
                                <td>ZWG {{ number_format((float) $transaction->zwg_third_party_premiums, 2) }}</td>
                                <td>ZWG {{ number_format((float) $transaction->zwg_full_cover_premiums, 2) }}</td>
                                <td>ZWG {{ number_format((float) $transaction->zwg_zinara_fees, 2) }}</td>
                                <td>ZWG {{ number_format((float) $transaction->zwg_cash, 2) }}</td>
                                <td>ZWG {{ number_format((float) $transaction->zwg_swipe, 2) }}</td>
                                <td>ZWG {{ number_format((float) $transaction->zwg_transfers, 2) }}</td>
                                <td>{{ $transaction->bank }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2">Totals</th>
                            <th>ZWG {{ number_format((float) $filteredTransactions->sum('zwg_insurance_transactions'), 2) }}</th>
                            <th>ZWG {{ number_format((float) $filteredTransactions->sum('zwg_third_party_premiums'), 2) }}</th>
                            <th>ZWG {{ number_format((float) $filteredTransactions->sum('zwg_full_cover_premiums'), 2) }}</th>
                            <th>ZWG {{ number_format((float) $filteredTransactions->sum('zwg_zinara_fees'), 2) }}</th>
                            <th>ZWG {{ number_format((float) $filteredTransactions->sum('zwg_cash'), 2) }}</th>
                            <th>ZWG {{ number_format((float) $filteredTransactions->sum('zwg_swipe'), 2) }}</th>
                            <th>ZWG {{ number_format((float) $filteredTransactions->sum('zwg_transfers'), 2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@else
    <div class="empty-state">
        {{ $hasFilters ? 'No collection records matched the selected user and date range.' : 'Select a user and date range to generate a user-level report.' }}
    </div>
@endif

<script>
const userLineChartLabels = @json($userLineChartLabels);
const userLineChartUsdData = @json($userLineChartUsdData);
const userLineChartZwgData = @json($userLineChartZwgData);
const userBarChartLabels = @json($userBarChartLabels);
const userBarChartUsdData = @json($userBarChartUsdData);
const userBarChartZwgData = @json($userBarChartZwgData);

function exportUserTable(tableId, filename, sheetName) {
    const table = document.getElementById(tableId);
    if (!table) {
        return;
    }

    const workbook = XLSX.utils.table_to_book(table, { sheet: sheetName });
    XLSX.writeFile(workbook, filename);
}

document.addEventListener('DOMContentLoaded', () => {
    const baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    color: '#173138',
                    usePointStyle: true,
                    boxWidth: 12
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(17, 36, 47, 0.08)'
                },
                ticks: {
                    color: '#5f7274'
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#5f7274'
                }
            }
        }
    };

    if (document.querySelector('#userLineChart')) {
        new Chart(document.querySelector('#userLineChart'), {
            type: 'line',
            data: {
                labels: userLineChartLabels,
                datasets: [
                    {
                        label: 'USD Insurance Transactions',
                        data: userLineChartUsdData,
                        borderColor: '#0f6b6e',
                        backgroundColor: 'rgba(15, 107, 110, 0.14)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'ZWG Insurance Transactions',
                        data: userLineChartZwgData,
                        borderColor: '#d97745',
                        backgroundColor: 'rgba(217, 119, 69, 0.14)',
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: baseOptions
        });
    }

    if (document.querySelector('#userBarChart')) {
        new Chart(document.querySelector('#userBarChart'), {
            type: 'bar',
            data: {
                labels: userBarChartLabels,
                datasets: [
                    {
                        label: 'USD Insurance Transactions by Site',
                        data: userBarChartUsdData,
                        backgroundColor: 'rgba(15, 107, 110, 0.82)',
                        borderRadius: 12
                    },
                    {
                        label: 'ZWG Insurance Transactions by Site',
                        data: userBarChartZwgData,
                        backgroundColor: 'rgba(217, 119, 69, 0.82)',
                        borderRadius: 12
                    }
                ]
            },
            options: baseOptions
        });
    }
});
</script>
@endsection
