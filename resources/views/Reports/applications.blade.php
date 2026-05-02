@extends('layouts.main')

@section('title')
Application Premium Reports
@endsection

@section('content')
@include('includes.header')
@include('includes.sidebar')

<div class="pagetitle">
    <h1>Application Premium Reports</h1>
    <p>Analyse uploaded application and premium records by date, status, classification, insurance type, agent, and location.</p>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Filter Application Records</h5>
        <div class="glass-note mb-3">
            Narrow the imported application dataset using the filters below, then review the summary cards, breakdown charts, and detailed exportable table.
        </div>

        <form class="row g-3" method="GET" action="{{ route('reports.applications') }}">
            <div class="col-md-3">
                <label for="startdate" class="form-label">Start Date</label>
                <input type="date" class="form-control" name="startdate" id="startdate" value="{{ request('startdate') }}">
            </div>
            <div class="col-md-3">
                <label for="enddate" class="form-label">End Date</label>
                <input type="date" class="form-control" name="enddate" id="enddate" value="{{ request('enddate') }}">
            </div>
            <div class="col-md-3">
                <label for="agent" class="form-label">Agent</label>
                <input type="text" class="form-control" name="agent" id="agent" value="{{ request('agent') }}" placeholder="Filter by agent">
            </div>
            <div class="col-md-3">
                <label for="location" class="form-label">Location</label>
                <input type="text" class="form-control" name="location" id="location" value="{{ request('location') }}" placeholder="Filter by location">
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" name="status" id="status">
                    <option value="">All Statuses</option>
                    @foreach($statusOptions as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="classification" class="form-label">Classification</label>
                <select class="form-select" name="classification" id="classification">
                    <option value="">All Classifications</option>
                    @foreach($classificationOptions as $classification)
                        <option value="{{ $classification }}" {{ request('classification') === $classification ? 'selected' : '' }}>{{ $classification }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="insurance_type" class="form-label">Insurance Type</label>
                <select class="form-select" name="insurance_type" id="insurance_type">
                    <option value="">All Insurance Types</option>
                    @foreach($insuranceTypeOptions as $insuranceType)
                        <option value="{{ $insuranceType }}" {{ request('insurance_type') === $insuranceType ? 'selected' : '' }}>{{ $insuranceType }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="{{ route('reports.applications') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

@if(empty($applicationSummary))
    <div class="empty-state">
        The application dataset is not available in this environment yet. Upload CSV records first, then return to this report.
    </div>
@else
    <section class="metric-grid mb-4">
        @foreach($applicationSummary as $card)
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
                        <h5 class="card-title mb-1">Status Breakdown</h5>
                        <div class="muted">Distribution of application records by current status.</div>
                    </div>
                    <span class="soft-chip"><i class="bi bi-pie-chart"></i> Status mix</span>
                </div>
                <canvas id="applicationStatusChart" style="max-height: 340px;"></canvas>
            </div>
        </div>

        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h5 class="card-title mb-1">Classification Breakdown</h5>
                        <div class="muted">Most common application classifications in the filtered slice.</div>
                    </div>
                    <span class="soft-chip"><i class="bi bi-funnel"></i> Classification mix</span>
                </div>
                <canvas id="applicationClassificationChart" style="max-height: 340px;"></canvas>
            </div>
        </div>
    </div>

    <div class="surface-grid two-up mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h5 class="card-title mb-1">Top Agents</h5>
                        <div class="muted">Agents with the highest number of filtered application records.</div>
                    </div>
                    <span class="soft-chip"><i class="bi bi-people"></i> Leaderboard</span>
                </div>

                @if($topAgents->isEmpty())
                    <div class="empty-state">No agent records match the current filters.</div>
                @else
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Agent</th>
                                    <th>Applications</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topAgents as $row)
                                    <tr>
                                        <td>{{ $row['agent'] }}</td>
                                        <td>{{ number_format($row['count']) }}</td>
                                        <td>${{ number_format($row['amount'], 2) }}</td>
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
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h5 class="card-title mb-1">Top Locations</h5>
                        <div class="muted">Locations with the largest application volume in the filtered slice.</div>
                    </div>
                    <span class="soft-chip"><i class="bi bi-geo-alt"></i> Location leaderboard</span>
                </div>

                @if($topLocations->isEmpty())
                    <div class="empty-state">No location records match the current filters.</div>
                @else
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Location</th>
                                    <th>Applications</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topLocations as $row)
                                    <tr>
                                        <td>{{ $row['location'] }}</td>
                                        <td>{{ number_format($row['count']) }}</td>
                                        <td>${{ number_format($row['amount'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Detailed Application Records</h5>
                    <div class="muted">Export the filtered dataset when you need to continue analysis outside the application.</div>
                </div>
                <button class="btn btn-primary" type="button" onclick="exportApplicationsReport()">
                    <i class="bi bi-download"></i> Export to Excel
                </button>
            </div>

            @if($records->isEmpty())
                <div class="empty-state">No application records match the current filters.</div>
            @else
                <div class="table-responsive table-shell">
                    <table class="table table-striped datatable" id="applicationsReportTable">
                        <thead>
                            <tr>
                                <th>Issue Date</th>
                                <th>Agent</th>
                                <th>Main Agent</th>
                                <th>Customer</th>
                                <th>Policy No</th>
                                <th>Status</th>
                                <th>Classification</th>
                                <th>Insurance Type</th>
                                <th>Location</th>
                                <th>Vehicle Reg</th>
                                <th>Approved By</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records as $record)
                                <tr>
                                    <td>{{ $record->issue_date }}</td>
                                    <td>{{ $record->agent }}</td>
                                    <td>{{ $record->main_agent }}</td>
                                    <td>{{ $record->customer_name }}</td>
                                    <td>{{ $record->policy_no }}</td>
                                    <td>{{ $record->status }}</td>
                                    <td>{{ $record->classification }}</td>
                                    <td>{{ $record->insurance_type }}</td>
                                    <td>{{ $record->location }}</td>
                                    <td>{{ $record->vehicle_reg_no }}</td>
                                    <td>{{ $record->approved }}</td>
                                    <td>{{ $record->amount }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        if (document.querySelector("#applicationStatusChart")) {
            new Chart(document.querySelector("#applicationStatusChart"), {
                type: "doughnut",
                data: {
                    labels: @json($statusChart->pluck('label')->toArray()),
                    datasets: [{
                        data: @json($statusChart->pluck('count')->toArray()),
                        backgroundColor: [
                            "#0f6b6e",
                            "#d97745",
                            "#8fc7bb",
                            "#204f5c",
                            "#f0b56d",
                            "#6f8f96",
                            "#c0d7d1",
                            "#915235"
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: "bottom",
                            labels: {
                                color: "#173138",
                                usePointStyle: true,
                                boxWidth: 12
                            }
                        }
                    }
                }
            });
        }

        if (document.querySelector("#applicationClassificationChart")) {
            new Chart(document.querySelector("#applicationClassificationChart"), {
                type: "bar",
                data: {
                    labels: @json($classificationChart->pluck('label')->toArray()),
                    datasets: [{
                        label: "Applications",
                        data: @json($classificationChart->pluck('count')->toArray()),
                        backgroundColor: "rgba(15, 107, 110, 0.82)",
                        borderRadius: 12
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: "top",
                            labels: {
                                color: "#173138",
                                usePointStyle: true
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: "rgba(17, 36, 47, 0.08)"
                            },
                            ticks: {
                                color: "#5f7274"
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: "#5f7274"
                            }
                        }
                    }
                }
            });
        }
    });

    function exportApplicationsReport() {
        const table = document.getElementById("applicationsReportTable");
        if (!table) {
            return;
        }

        const workbook = XLSX.utils.table_to_book(table, { sheet: "Applications Report" });
        XLSX.writeFile(workbook, "application_premium_report.xlsx");
    }
    </script>
@endif
@endsection
