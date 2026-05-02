@extends('layouts.main')

@section('title')
Dashboard
@endsection

@section('content')
@include('includes.header')
@include('includes.sidebar')

@php
    $user = Auth::user();
    $roleLabels = [
        1 => 'Admin',
        2 => 'Clerk',
        3 => 'Supervisor',
        4 => 'Manager',
        5 => 'Super User',
        6 => 'Courier Supervisor',
        7 => 'Courier Clerk',
    ];
    $roleLabel = $roleLabels[$user->role_id] ?? 'Workspace User';
    $isZINARASupervisor = ($user->role_id == 6);
    $isZINARAClerk = ($user->role_id == 7);
    $isZINARAUser = ($isZINARASupervisor || $isZINARAClerk);
    $hasCharts = count($lineChartLabels) || count($barChartLabels) || count($networkReportLabels);
@endphp

<div class="pagetitle">
    <h1>Operational Dashboard</h1>
    <p>
        @if($isZINARAUser)
            Courier-focused dashboard for face value management, declarations, and stock tracking.
        @else
            Use this command center to track collection performance, workload, and review priorities across your current reporting scope.
        @endif
    </p>
</div>

<section class="dashboard-hero mb-4">
    <div>
        <div class="hero-spotlight">
            @if($isZINARAUser)
                Courier Performance View
            @else
                GRUMA Performance View
            @endif
        </div>
        <h2>{{ $roleLabel }} workspace with immediate operational context.</h2>
        <p>
            @if($isZINARASupervisor)
                Monitor face value stock, allocations to clerks, declaration activity, and spoilage across your Courier clerks.
            @elseif($isZINARAClerk)
                Track your face value declarations, remaining stock balances, and declaration history.
            @else
                Focus on the signals that matter first: recent collection momentum, pending review work, active sites, and the fastest routes back into capture and reporting.
            @endif
        </p>
    </div>

    <div class="hero-side-panel">
        @foreach($dashboardSpotlights as $spotlight)
            <div class="hero-side-card">
                <span>{{ $spotlight['label'] }}</span>
                <strong>{{ $spotlight['value'] }}</strong>
            </div>
        @endforeach
    </div>
</section>

<section class="metric-grid mb-4">
    @foreach($summaryCards as $card)
        <article class="metric-card">
            <span class="metric-label"><i class="{{ $card['icon'] }}"></i> {{ $card['label'] }}</span>
            <strong class="metric-value">{{ $card['value'] }}</strong>
            <div class="metric-note">{{ $card['note'] }}</div>
        </article>
    @endforeach
</section>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Quick Actions</h5>
                <div class="muted">Shortcuts to the highest-value actions for your current role.</div>
            </div>
            <span class="workspace-chip"><i class="bi bi-lightning-charge"></i> Role-aware shortcuts</span>
        </div>

        <div class="quick-action-grid">
            @foreach($quickActions as $action)
                <a href="{{ $action['route'] }}" class="quick-action-card text-decoration-none">
                    <div class="quick-action-icon"><i class="{{ $action['icon'] }}"></i></div>
                    <h3>{{ $action['label'] }}</h3>
                    <p>{{ $action['description'] }}</p>
                    <span class="soft-chip">Open</span>
                </a>
            @endforeach
        </div>
    </div>
</div>

@if($isZINARAUser)
    {{-- ZINARA-specific charts and data --}}
    <div class="surface-grid two-up mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h5 class="card-title mb-1">Face Value Declarations Trend</h5>
                        <div class="muted">Daily used and spoiled face values over the last 30 days.</div>
                    </div>
                    <span class="soft-chip"><i class="bi bi-calendar-week"></i> Rolling 30 days</span>
                </div>
                <canvas id="zinaraLineChart" style="max-height: 360px;"></canvas>
            </div>
        </div>

        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h5 class="card-title mb-1">Stock vs Allocated</h5>
                        <div class="muted">Current face value stock distribution across clerks.</div>
                    </div>
                    <span class="soft-chip"><i class="bi bi-pie-chart"></i> Current status</span>
                </div>
                <canvas id="zinaraPieChart" style="max-height: 360px;"></canvas>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Clerk Stock Balances</h5>
                    <div class="muted">Current face value balances by clerk (top 10).</div>
                </div>
                <span class="soft-chip"><i class="bi bi-people"></i> Active clerks</span>
            </div>
            <canvas id="zinaraBarChart" style="max-height: 380px;"></canvas>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Recent Face Value Declarations</h5>
                    <div class="muted">Latest declaration activity in your scope.</div>
                </div>
                <span class="soft-chip"><i class="bi bi-clock-history"></i> Recent entries</span>
            </div>

            @if($recentDeclarations->isEmpty())
                <div class="empty-state">No recent face value declarations found.</div>
            @else
                <div class="table-responsive table-shell">
                    <table class="table activity-table">
                        <thead>
                            <tr>
                                <th>Clerk</th>
                                <th>Site</th>
                                <th>Batch ID</th>
                                <th>Used</th>
                                <th>Spoiled</th>
                                <th>Balance After</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentDeclarations as $declaration)
                                <tr>
                                    <td><strong>{{ $declaration->clerk_name ?? 'Unknown' }}</strong></td>
                                    <td>{{ $declaration->site_name ?? 'N/A' }}</td>
                                    <td>{{ $declaration->batch_id ?? 'N/A' }}</td>
                                    <td>{{ number_format($declaration->used ?? 0, 0) }}</td>
                                    <td>{{ number_format($declaration->spoiled ?? 0, 0) }}</td>
                                    <td>{{ number_format($declaration->closing_balance ?? 0, 0) }}</td>
                                    <td>{{ \Carbon\Carbon::parse($declaration->created_at)->format('d M Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if($isZINARASupervisor)
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Low Stock Alerts</h5>
                    <div class="muted">Clerks with face value balance below threshold (20 units).</div>
                </div>
                <span class="soft-chip"><i class="bi bi-exclamation-triangle"></i> Needs attention</span>
            </div>

            @if($lowStockAlerts->isEmpty())
                <div class="empty-state">All clerks have healthy stock levels.</div>
            @else
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Clerk</th>
                                <th>Site</th>
                                <th>Current Balance</th>
                                <th>Last Activity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowStockAlerts as $alert)
                                <tr>
                                    <td>{{ $alert->clerk_name }}</td>
                                    <td>{{ $alert->site_name }}</td>
                                    <td><span class="badge bg-warning">{{ number_format($alert->balance, 0) }} units</span></td>
                                    <td>{{ $alert->last_activity ?? 'No activity' }}</td>
                                    <td>
                                        <a href="{{ route('supervisorfacevalues.allocate', ['id' => $alert->batch_id]) }}" class="btn btn-sm btn-primary">
                                            Allocate Stock
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    @endif

@else
    {{-- Regular GRUMA charts and data --}}
    @if($hasCharts)
        <div class="surface-grid two-up mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                        <div>
                            <h5 class="card-title mb-1">Daily Collection Trend</h5>
                            <div class="muted">Last 30 days of submitted USD and ZWG activity.</div>
                        </div>
                        <span class="soft-chip"><i class="bi bi-calendar-week"></i> Rolling 30 days</span>
                    </div>
                    <canvas id="lineChart" style="max-height: 360px;"></canvas>
                </div>
            </div>

            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                        <div>
                            <h5 class="card-title mb-1">Network Mix</h5>
                            <div class="muted">Compare transaction volume across the strongest networks in view.</div>
                        </div>
                        <span class="soft-chip"><i class="bi bi-diagram-3"></i> Network ranking</span>
                    </div>
                    <canvas id="networkChart" style="max-height: 360px;"></canvas>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h5 class="card-title mb-1">Site Distribution</h5>
                        <div class="muted">Where submitted activity is currently concentrated.</div>
                    </div>
                    <span class="soft-chip"><i class="bi bi-building"></i> Top active sites</span>
                </div>
                <canvas id="barChart" style="max-height: 380px;"></canvas>
            </div>
        </div>
    @else
        <div class="empty-state mb-4">
            No collection data is available yet for the current reporting scope. Once transactions are submitted, the dashboard charts will appear here.
        </div>
    @endif

    <div class="surface-grid two-up">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h5 class="card-title mb-1">Recent Activity</h5>
                        <div class="muted">Latest collection submissions in your current scope.</div>
                    </div>
                    <span class="soft-chip"><i class="bi bi-clock-history"></i> Freshest entries</span>
                </div>

                @if($recentCollections->isEmpty())
                    <div class="empty-state">No recent submissions found yet.</div>
                @else
                    <div class="table-responsive table-shell">
                        <table class="table activity-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Site</th>
                                    <th>USD</th>
                                    <th>ZWG</th>
                                    <th>Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentCollections as $entry)
                                    <tr>
                                        <td>
                                            <strong>{{ $entry->username ?: 'Unknown User' }}</strong><br>
                                            <small>{{ $entry->bank ?: 'No bank recorded' }}</small>
                                         </td>
                                        <td>{{ $entry->site_name ?: 'Unassigned Site' }}</td>
                                        <td>${{ number_format($entry->insurance_transactions ?? 0, 2) }}</td>
                                        <td>ZWG {{ number_format($entry->zwg_insurance_transactions ?? 0, 2) }}</td>
                                        <td>{{ \Carbon\Carbon::parse($entry->created_at)->format('d M Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Operational Notes</h5>

                <div class="glass-note mb-3">
                    <strong>Pending amendments:</strong> {{ number_format($pendingAmendments) }} request{{ $pendingAmendments == 1 ? '' : 's' }} currently waiting for action.
                </div>

                <div class="quick-action-grid">
                    <article class="quick-action-card">
                        <div class="quick-action-icon"><i class="bi bi-clipboard2-pulse"></i></div>
                        <h3>Collection Pulse</h3>
                        <p>Monitor the 30-day trend charts to see whether activity is consolidating into a few sites or spreading across the network.</p>
                    </article>

                    <article class="quick-action-card">
                        <div class="quick-action-icon"><i class="bi bi-shield-check"></i></div>
                        <h3>Control Reminder</h3>
                        <p>Keep amendment queues short and face value balances reconciled so reporting stays reliable and easier to audit.</p>
                    </article>
                </div>
            </div>
        </div>
    </div>
@endif

@if($hasCharts || $isZINARAUser)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const sharedGridColor = "rgba(17, 36, 47, 0.08)";
        const sharedTickColor = "#5f7274";

        const chartBaseOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: "top",
                    labels: {
                        boxWidth: 12,
                        usePointStyle: true,
                        color: "#173138",
                        font: {
                            family: "Space Grotesk"
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: sharedGridColor
                    },
                    ticks: {
                        color: sharedTickColor
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: sharedTickColor
                    }
                }
            }
        };

        @if($isZINARAUser)
            // ZINARA Line Chart
            if (document.querySelector("#zinaraLineChart")) {
                new Chart(document.querySelector("#zinaraLineChart"), {
                    type: "line",
                    data: {
                        labels: @json($zinaraLineChartLabels ?? []),
                        datasets: [
                            {
                                label: "Used Face Values",
                                data: @json($zinaraUsedData ?? []),
                                borderColor: "#d97745",
                                backgroundColor: "rgba(217, 119, 69, 0.15)",
                                fill: true,
                                tension: 0.28
                            },
                            {
                                label: "Spoiled Face Values",
                                data: @json($zinaraSpoiledData ?? []),
                                borderColor: "#b91c1c",
                                backgroundColor: "rgba(185, 28, 28, 0.12)",
                                fill: true,
                                tension: 0.28
                            }
                        ]
                    },
                    options: chartBaseOptions
                });
            }

            // ZINARA Pie Chart
            if (document.querySelector("#zinaraPieChart")) {
                new Chart(document.querySelector("#zinaraPieChart"), {
                    type: "pie",
                    data: {
                        labels: @json($zinaraPieLabels ?? ['In Stock', 'Allocated', 'Used/Spoiled']),
                        datasets: [
                            {
                                data: @json($zinaraPieData ?? [0, 0, 0]),
                                backgroundColor: ["#0f6b6e", "#d97745", "#b91c1c"],
                                borderRadius: 12
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: "bottom",
                                labels: {
                                    font: {
                                        family: "Space Grotesk"
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // ZINARA Bar Chart
            if (document.querySelector("#zinaraBarChart")) {
                new Chart(document.querySelector("#zinaraBarChart"), {
                    type: "bar",
                    data: {
                        labels: @json($clerkBalanceLabels ?? []),
                        datasets: [
                            {
                                label: "Current Balance",
                                data: @json($clerkBalanceData ?? []),
                                backgroundColor: "rgba(15, 107, 110, 0.8)",
                                borderRadius: 12
                            }
                        ]
                    },
                    options: chartBaseOptions
                });
            }
        @else
            // Regular GRUMA Charts
            if (document.querySelector("#lineChart")) {
                new Chart(document.querySelector("#lineChart"), {
                    type: "line",
                    data: {
                        labels: @json($lineChartLabels),
                        datasets: [
                            {
                                label: "USD Collections",
                                data: @json($lineChartUsdData),
                                borderColor: "#0f6b6e",
                                backgroundColor: "rgba(15, 107, 110, 0.15)",
                                fill: true,
                                tension: 0.28
                            },
                            {
                                label: "ZWG Collections",
                                data: @json($lineChartZwgData),
                                borderColor: "#d97745",
                                backgroundColor: "rgba(217, 119, 69, 0.12)",
                                fill: true,
                                tension: 0.28
                            }
                        ]
                    },
                    options: chartBaseOptions
                });
            }

            if (document.querySelector("#networkChart")) {
                new Chart(document.querySelector("#networkChart"), {
                    type: "bar",
                    data: {
                        labels: @json($networkReportLabels),
                        datasets: [
                            {
                                label: "USD",
                                data: @json($networkReportUsdData),
                                backgroundColor: "rgba(15, 107, 110, 0.8)",
                                borderRadius: 12
                            },
                            {
                                label: "ZWG",
                                data: @json($networkReportZwgData),
                                backgroundColor: "rgba(217, 119, 69, 0.75)",
                                borderRadius: 12
                            }
                        ]
                    },
                    options: chartBaseOptions
                });
            }

            if (document.querySelector("#barChart")) {
                new Chart(document.querySelector("#barChart"), {
                    type: "bar",
                    data: {
                        labels: @json($barChartLabels),
                        datasets: [
                            {
                                label: "USD",
                                data: @json($barChartUsdData),
                                backgroundColor: "rgba(15, 107, 110, 0.82)",
                                borderRadius: 12
                            },
                            {
                                label: "ZWG",
                                data: @json($barChartZwgData),
                                backgroundColor: "rgba(143, 199, 187, 0.9)",
                                borderRadius: 12
                            }
                        ]
                    },
                    options: chartBaseOptions
                });
            }
        @endif
    });
</script>
@endif
@endsection