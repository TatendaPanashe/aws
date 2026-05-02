@extends('layouts.main')

@section('title')
Cumulative Network Reports
@endsection

@section('content')
@include('includes.header')
@include('includes.sidebar')

@php
    $usdRows = collect($usdcompiledFigures);
    $zwgRows = collect($zwgcompiledFigures);
    $amountPill = function ($value, $tone = 'neutral') {
        $value = (float) $value;

        if ($value == 0.0) {
            return 'report-pill report-pill--neutral';
        }

        $tones = [
            'success' => 'report-pill report-pill--success',
            'warning' => 'report-pill report-pill--warning',
            'danger' => 'report-pill report-pill--danger',
            'info' => 'report-pill report-pill--info',
        ];

        return $tones[$tone] ?? 'report-pill report-pill--neutral';
    };
    $collectionStatus = function ($transactions, $deposits) {
        $transactions = (float) $transactions;
        $deposits = (float) $deposits;

        if ($transactions <= 0 && $deposits <= 0) {
            return [
                'label' => 'No Activity',
                'class' => 'report-pill report-pill--neutral',
                'row_class' => '',
            ];
        }

        if ($deposits >= $transactions) {
            return [
                'label' => 'Covered',
                'class' => 'report-pill report-pill--success',
                'row_class' => '',
            ];
        }

        if ($deposits > 0) {
            return [
                'label' => 'Partial Deposit',
                'class' => 'report-pill report-pill--warning',
                'row_class' => 'report-row--warning',
            ];
        }

        return [
            'label' => 'Undeposited',
            'class' => 'report-pill report-pill--danger',
            'row_class' => 'report-row--danger',
        ];
    };
    $activeNetworks = $usdRows
        ->filter(fn ($row) => collect($row)->except('networkname')->sum() > 0)
        ->count();
    $summaryCards = [
        [
            'label' => 'Networks In Scope',
            'value' => number_format($usdRows->count()),
            'note' => 'Networks included in the current cumulative view.',
            'icon' => 'bi bi-diagram-3',
        ],
        [
            'label' => 'Active Networks',
            'value' => number_format($activeNetworks),
            'note' => 'Networks with activity in the selected reporting window.',
            'icon' => 'bi bi-broadcast',
        ],
        [
            'label' => 'USD Transactions',
            'value' => '$' . number_format((float) $usdRows->sum('insurance_transactions'), 2),
            'note' => 'Cumulative USD insurance transaction value across all listed networks.',
            'icon' => 'bi bi-cash-stack',
        ],
        [
            'label' => 'ZWG Transactions',
            'value' => 'ZWG ' . number_format((float) $zwgRows->sum('zwg_insurance_transactions'), 2),
            'note' => 'Cumulative ZWG insurance transaction value across all listed networks.',
            'icon' => 'bi bi-wallet2',
        ],
        [
            'label' => 'USD Deposits',
            'value' => '$' . number_format((float) $usdRows->sum('usd_total_deposited'), 2),
            'note' => 'Total USD deposits recorded in the reporting window.',
            'icon' => 'bi bi-bank',
        ],
        [
            'label' => 'ZWG Deposits',
            'value' => 'ZWG ' . number_format((float) $zwgRows->sum('zwg_total_deposited'), 2),
            'note' => 'Total ZWG deposits recorded in the reporting window.',
            'icon' => 'bi bi-safe2',
        ],
    ];
@endphp

@once
    <style>
        .report-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .report-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 6.5rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            border: 1px solid transparent;
            font-size: 0.82rem;
            font-weight: 700;
            line-height: 1.2;
            white-space: nowrap;
        }

        .report-pill--success {
            background: rgba(22, 101, 52, 0.12);
            border-color: rgba(22, 101, 52, 0.18);
            color: #166534;
        }

        .report-pill--warning {
            background: rgba(180, 83, 9, 0.12);
            border-color: rgba(180, 83, 9, 0.18);
            color: #b45309;
        }

        .report-pill--danger {
            background: rgba(185, 28, 28, 0.12);
            border-color: rgba(185, 28, 28, 0.18);
            color: #b91c1c;
        }

        .report-pill--info {
            background: rgba(8, 145, 178, 0.12);
            border-color: rgba(8, 145, 178, 0.18);
            color: #155e75;
        }

        .report-pill--neutral {
            background: rgba(100, 116, 139, 0.12);
            border-color: rgba(100, 116, 139, 0.18);
            color: #475569;
        }

        .report-row--warning td {
            background: rgba(245, 158, 11, 0.06) !important;
        }

        .report-row--danger td {
            background: rgba(239, 68, 68, 0.06) !important;
        }
    </style>
@endonce

<div class="pagetitle">
    <h1>Cumulative Network Reports</h1>
    <p>Compare network-level transaction, premium, and deposit totals over a reporting window and export the cumulative tables when needed.</p>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Filter Reporting Window</h5>
                <div class="muted">The current report covers {{ $startdate }} to {{ $enddate }}.</div>
            </div>
            <a href="{{ route('reports.hub') }}" class="btn btn-secondary">
                <i class="bi bi-grid-1x2"></i> Reports Hub
            </a>
        </div>

        <div class="glass-note mb-3">
            This report aggregates each network into one USD row and one ZWG row for the selected period, making it useful for executive review and month-end reconciliation.
        </div>

        <form class="row g-3" method="post" action="{{ route('cumulativeNetworkReport') }}">
            @csrf
            <div class="col-md-4">
                <label for="startdate" class="form-label">Start Date</label>
                <input type="date" class="form-control" name="startdate" id="startdate" value="{{ $startdate }}">
            </div>
            <div class="col-md-4">
                <label for="enddate" class="form-label">End Date</label>
                <input type="date" class="form-control" name="enddate" id="enddate" value="{{ $enddate }}">
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">Apply Window</button>
                <a href="{{ route('cumulativeNetworkReport') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

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
                    <h5 class="card-title mb-1">USD Network Comparison</h5>
                    <div class="muted">Insurance transaction totals and deposits by network.</div>
                </div>
                <span class="soft-chip"><i class="bi bi-bar-chart-line"></i> USD focus</span>
            </div>
            <canvas id="usdNetworkChart" style="max-height: 360px;"></canvas>
        </div>
    </div>

    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">ZWG Network Comparison</h5>
                    <div class="muted">Insurance transaction totals and deposits by network.</div>
                </div>
                <span class="soft-chip"><i class="bi bi-bar-chart-steps"></i> ZWG focus</span>
            </div>
            <canvas id="zwgNetworkChart" style="max-height: 360px;"></canvas>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">USD Network Totals</h5>
                <div class="muted">Cumulative USD transaction and channel totals by network.</div>
            </div>
            <button class="btn btn-primary" type="button" onclick="exportTableToExcel('usdTable', 'Cumulative_USD_Network_Report.xlsx', 'USD Network Report')">
                <i class="bi bi-download"></i> Export USD
            </button>
        </div>

        <div class="report-legend">
            <span class="report-pill report-pill--info">Collection Totals</span>
            <span class="report-pill report-pill--success">Deposits Cover Collections</span>
            <span class="report-pill report-pill--warning">Partial Deposit Coverage</span>
            <span class="report-pill report-pill--danger">No Deposit Against Activity</span>
        </div>

        <div class="table-responsive table-shell">
            <table class="table table-striped datatable" id="usdTable">
                <thead>
                    <tr>
                        <th>Network</th>
                        <th>Insurance Transactions</th>
                        <th>Zinara Fees</th>
                        <th>Third Party Premiums</th>
                        <th>Full Cover Premiums</th>
                        <th>USD Deposits</th>
                        <th>USD Cash</th>
                        <th>USD Swipe</th>
                        <th>USD Transfers</th>
                        <th>USD Debit Sales</th>
                        <th>USD Credit Sales</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($usdRows as $row)
                        @php($usdStatus = $collectionStatus($row['insurance_transactions'], $row['usd_total_deposited']))
                        <tr class="{{ $usdStatus['row_class'] }}">
                            <td>{{ $row['networkname'] }}</td>
                            <td><span class="{{ $amountPill($row['insurance_transactions'], 'info') }}">${{ number_format((float) $row['insurance_transactions'], 2) }}</span></td>
                            <td><span class="{{ $amountPill($row['zinara_transactions'], 'warning') }}">{{ number_format((float) $row['zinara_transactions'], 2) }}</span></td>
                            <td><span class="{{ $amountPill($row['third_party_premiums'], 'info') }}">${{ number_format((float) $row['third_party_premiums'], 2) }}</span></td>
                            <td><span class="{{ $amountPill($row['full_cover_premiums'], 'info') }}">${{ number_format((float) $row['full_cover_premiums'], 2) }}</span></td>
                            <td><span class="{{ $amountPill($row['usd_total_deposited'], $row['usd_total_deposited'] >= $row['insurance_transactions'] ? 'success' : ((float) $row['usd_total_deposited'] > 0 ? 'warning' : 'danger')) }}">${{ number_format((float) $row['usd_total_deposited'], 2) }}</span></td>
                            <td><span class="{{ $amountPill($row['usd_cash'], 'success') }}">${{ number_format((float) $row['usd_cash'], 2) }}</span></td>
                            <td><span class="{{ $amountPill($row['usd_swipe'], 'info') }}">${{ number_format((float) $row['usd_swipe'], 2) }}</span></td>
                            <td><span class="{{ $amountPill($row['usd_transfers'], 'info') }}">${{ number_format((float) $row['usd_transfers'], 2) }}</span></td>
                            <td><span class="{{ $amountPill($row['usd_debit_sales'], 'warning') }}">${{ number_format((float) $row['usd_debit_sales'], 2) }}</span></td>
                            <td><span class="{{ $amountPill($row['usd_credit_sales'], 'warning') }}">${{ number_format((float) $row['usd_credit_sales'], 2) }}</span></td>
                            <td><span class="{{ $usdStatus['class'] }}">{{ $usdStatus['label'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>Totals</th>
                        <th><span class="{{ $amountPill($usdRows->sum('insurance_transactions'), 'info') }}">${{ number_format((float) $usdRows->sum('insurance_transactions'), 2) }}</span></th>
                        <th><span class="{{ $amountPill($usdRows->sum('zinara_transactions'), 'warning') }}">{{ number_format((float) $usdRows->sum('zinara_transactions'), 2) }}</span></th>
                        <th><span class="{{ $amountPill($usdRows->sum('third_party_premiums'), 'info') }}">${{ number_format((float) $usdRows->sum('third_party_premiums'), 2) }}</span></th>
                        <th><span class="{{ $amountPill($usdRows->sum('full_cover_premiums'), 'info') }}">${{ number_format((float) $usdRows->sum('full_cover_premiums'), 2) }}</span></th>
                        <th><span class="{{ $amountPill($usdRows->sum('usd_total_deposited'), $usdRows->sum('usd_total_deposited') >= $usdRows->sum('insurance_transactions') ? 'success' : ((float) $usdRows->sum('usd_total_deposited') > 0 ? 'warning' : 'danger')) }}">${{ number_format((float) $usdRows->sum('usd_total_deposited'), 2) }}</span></th>
                        <th><span class="{{ $amountPill($usdRows->sum('usd_cash'), 'success') }}">${{ number_format((float) $usdRows->sum('usd_cash'), 2) }}</span></th>
                        <th><span class="{{ $amountPill($usdRows->sum('usd_swipe'), 'info') }}">${{ number_format((float) $usdRows->sum('usd_swipe'), 2) }}</span></th>
                        <th><span class="{{ $amountPill($usdRows->sum('usd_transfers'), 'info') }}">${{ number_format((float) $usdRows->sum('usd_transfers'), 2) }}</span></th>
                        <th><span class="{{ $amountPill($usdRows->sum('usd_debit_sales'), 'warning') }}">${{ number_format((float) $usdRows->sum('usd_debit_sales'), 2) }}</span></th>
                        <th><span class="{{ $amountPill($usdRows->sum('usd_credit_sales'), 'warning') }}">${{ number_format((float) $usdRows->sum('usd_credit_sales'), 2) }}</span></th>
                        <th>
                            @php($usdTotalsStatus = $collectionStatus($usdRows->sum('insurance_transactions'), $usdRows->sum('usd_total_deposited')))
                            <span class="{{ $usdTotalsStatus['class'] }}">{{ $usdTotalsStatus['label'] }}</span>
                        </th>
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
                <h5 class="card-title mb-1">ZWG Network Totals</h5>
                <div class="muted">Cumulative ZWG transaction and channel totals by network.</div>
            </div>
            <button class="btn btn-primary" type="button" onclick="exportTableToExcel('zwgnetworkTable', 'Cumulative_ZWG_Network_Report.xlsx', 'ZWG Network Report')">
                <i class="bi bi-download"></i> Export ZWG
            </button>
        </div>

        <div class="table-responsive table-shell">
            <table class="table table-striped datatable" id="zwgnetworkTable">
                <thead>
                    <tr>
                        <th>Network</th>
                        <th>Insurance Transactions</th>
                        <th>Zinara Fees</th>
                        <th>Third Party Premiums</th>
                        <th>Full Cover Premiums</th>
                        <th>ZWG Deposits</th>
                        <th>ZWG Cash</th>
                        <th>ZWG Swipe</th>
                        <th>ZWG Transfers</th>
                        <th>ZWG Debit Sales</th>
                        <th>ZWG Credit Sales</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($zwgRows as $row)
                        @php($zwgStatus = $collectionStatus($row['zwg_insurance_transactions'], $row['zwg_total_deposited']))
                        <tr class="{{ $zwgStatus['row_class'] }}">
                            <td>{{ $row['networkname'] }}</td>
                            <td><span class="{{ $amountPill($row['zwg_insurance_transactions'], 'info') }}">ZWG {{ number_format((float) $row['zwg_insurance_transactions'], 2) }}</span></td>
                            <td><span class="{{ $amountPill($row['zwg_zinara_fees'], 'warning') }}">{{ number_format((float) $row['zwg_zinara_fees'], 2) }}</span></td>
                            <td><span class="{{ $amountPill($row['zwg_third_party_premiums'], 'info') }}">ZWG {{ number_format((float) $row['zwg_third_party_premiums'], 2) }}</span></td>
                            <td><span class="{{ $amountPill($row['zwg_full_cover_premiums'], 'info') }}">ZWG {{ number_format((float) $row['zwg_full_cover_premiums'], 2) }}</span></td>
                            <td><span class="{{ $amountPill($row['zwg_total_deposited'], $row['zwg_total_deposited'] >= $row['zwg_insurance_transactions'] ? 'success' : ((float) $row['zwg_total_deposited'] > 0 ? 'warning' : 'danger')) }}">ZWG {{ number_format((float) $row['zwg_total_deposited'], 2) }}</span></td>
                            <td><span class="{{ $amountPill($row['zwg_cash'], 'success') }}">ZWG {{ number_format((float) $row['zwg_cash'], 2) }}</span></td>
                            <td><span class="{{ $amountPill($row['zwg_swipe'], 'info') }}">ZWG {{ number_format((float) $row['zwg_swipe'], 2) }}</span></td>
                            <td><span class="{{ $amountPill($row['zwg_transfers'], 'info') }}">ZWG {{ number_format((float) $row['zwg_transfers'], 2) }}</span></td>
                            <td><span class="{{ $amountPill($row['zwg_debit_sales'], 'warning') }}">ZWG {{ number_format((float) $row['zwg_debit_sales'], 2) }}</span></td>
                            <td><span class="{{ $amountPill($row['zwg_credit_sales'], 'warning') }}">ZWG {{ number_format((float) $row['zwg_credit_sales'], 2) }}</span></td>
                            <td><span class="{{ $zwgStatus['class'] }}">{{ $zwgStatus['label'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>Totals</th>
                        <th><span class="{{ $amountPill($zwgRows->sum('zwg_insurance_transactions'), 'info') }}">ZWG {{ number_format((float) $zwgRows->sum('zwg_insurance_transactions'), 2) }}</span></th>
                        <th><span class="{{ $amountPill($zwgRows->sum('zwg_zinara_fees'), 'warning') }}">{{ number_format((float) $zwgRows->sum('zwg_zinara_fees'), 2) }}</span></th>
                        <th><span class="{{ $amountPill($zwgRows->sum('zwg_third_party_premiums'), 'info') }}">ZWG {{ number_format((float) $zwgRows->sum('zwg_third_party_premiums'), 2) }}</span></th>
                        <th><span class="{{ $amountPill($zwgRows->sum('zwg_full_cover_premiums'), 'info') }}">ZWG {{ number_format((float) $zwgRows->sum('zwg_full_cover_premiums'), 2) }}</span></th>
                        <th><span class="{{ $amountPill($zwgRows->sum('zwg_total_deposited'), $zwgRows->sum('zwg_total_deposited') >= $zwgRows->sum('zwg_insurance_transactions') ? 'success' : ((float) $zwgRows->sum('zwg_total_deposited') > 0 ? 'warning' : 'danger')) }}">ZWG {{ number_format((float) $zwgRows->sum('zwg_total_deposited'), 2) }}</span></th>
                        <th><span class="{{ $amountPill($zwgRows->sum('zwg_cash'), 'success') }}">ZWG {{ number_format((float) $zwgRows->sum('zwg_cash'), 2) }}</span></th>
                        <th><span class="{{ $amountPill($zwgRows->sum('zwg_swipe'), 'info') }}">ZWG {{ number_format((float) $zwgRows->sum('zwg_swipe'), 2) }}</span></th>
                        <th><span class="{{ $amountPill($zwgRows->sum('zwg_transfers'), 'info') }}">ZWG {{ number_format((float) $zwgRows->sum('zwg_transfers'), 2) }}</span></th>
                        <th><span class="{{ $amountPill($zwgRows->sum('zwg_debit_sales'), 'warning') }}">ZWG {{ number_format((float) $zwgRows->sum('zwg_debit_sales'), 2) }}</span></th>
                        <th><span class="{{ $amountPill($zwgRows->sum('zwg_credit_sales'), 'warning') }}">ZWG {{ number_format((float) $zwgRows->sum('zwg_credit_sales'), 2) }}</span></th>
                        <th>
                            @php($zwgTotalsStatus = $collectionStatus($zwgRows->sum('zwg_insurance_transactions'), $zwgRows->sum('zwg_total_deposited')))
                            <span class="{{ $zwgTotalsStatus['class'] }}">{{ $zwgTotalsStatus['label'] }}</span>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
function exportTableToExcel(tableId, filename, sheetName) {
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

    if (document.querySelector('#usdNetworkChart')) {
        new Chart(document.querySelector('#usdNetworkChart'), {
            type: 'bar',
            data: {
                labels: @json($usdRows->pluck('networkname')->toArray()),
                datasets: [
                    {
                        label: 'USD Transactions',
                        data: @json($usdRows->pluck('insurance_transactions')->map(fn ($value) => (float) $value)->toArray()),
                        backgroundColor: 'rgba(15, 107, 110, 0.82)',
                        borderRadius: 12
                    },
                    {
                        label: 'USD Deposits',
                        data: @json($usdRows->pluck('usd_total_deposited')->map(fn ($value) => (float) $value)->toArray()),
                        backgroundColor: 'rgba(143, 199, 187, 0.85)',
                        borderRadius: 12
                    }
                ]
            },
            options: baseOptions
        });
    }

    if (document.querySelector('#zwgNetworkChart')) {
        new Chart(document.querySelector('#zwgNetworkChart'), {
            type: 'bar',
            data: {
                labels: @json($zwgRows->pluck('networkname')->toArray()),
                datasets: [
                    {
                        label: 'ZWG Transactions',
                        data: @json($zwgRows->pluck('zwg_insurance_transactions')->map(fn ($value) => (float) $value)->toArray()),
                        backgroundColor: 'rgba(217, 119, 69, 0.82)',
                        borderRadius: 12
                    },
                    {
                        label: 'ZWG Deposits',
                        data: @json($zwgRows->pluck('zwg_total_deposited')->map(fn ($value) => (float) $value)->toArray()),
                        backgroundColor: 'rgba(240, 181, 109, 0.88)',
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
