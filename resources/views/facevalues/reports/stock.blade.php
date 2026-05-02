@extends('layouts.main')

@section('title')
Face Value Stock Report
@endsection

@section('content')
@include('includes.header')
@include('includes.sidebar')

@php
    $roleId = (int) Auth::user()->role_id;
    $showSbuFilter = in_array($roleId, [1, 3, 5, 6], true);
    $isSbuLocked = in_array($roleId, [3, 6], true) && filled($resolvedSbu ?? null);
@endphp

<div class="pagetitle">
    <h1>Face Value Stock And Allocation Report</h1>
    <p>Track face values received into supervisor stock, allocations sent to clerks, and remaining balances by batch.</p>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Filter Stock Report</h5>
                <div class="muted">Use the reporting window to inspect batches received and allocations made over time.</div>
            </div>
            <a href="{{ route('facevalues.reports.hub') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Reports Hub
            </a>
        </div>

        <form method="GET" action="{{ route('facevalues.reports.stock') }}" class="row g-3">
            @if($showSbuFilter)
                <div class="col-md-4">
                    <label for="sbu" class="form-label">SBU</label>
                    <select id="sbu" name="sbu" class="form-select" {{ $isSbuLocked ? 'disabled' : '' }}>
                        <option value="">All SBUs</option>
                        @foreach($sbuOptions as $sbu)
                            <option value="{{ $sbu }}" {{ (string) ($resolvedSbu ?? '') === (string) $sbu ? 'selected' : '' }}>{{ $sbu }}</option>
                        @endforeach
                    </select>
                    @if($isSbuLocked)
                        <input type="hidden" name="sbu" value="{{ $resolvedSbu }}">
                    @endif
                </div>
            @endif
            <div class="col-md-4">
                <label for="startdate" class="form-label">Start Date</label>
                <input type="date" id="startdate" name="startdate" class="form-control" value="{{ $startDate->toDateString() }}">
            </div>
            <div class="col-md-4">
                <label for="enddate" class="form-label">End Date</label>
                <input type="date" id="enddate" name="enddate" class="form-control" value="{{ $endDate->toDateString() }}">
            </div>
            <div class="col-12 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="{{ route('facevalues.reports.stock') }}" class="btn btn-secondary">Reset</a>
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
                    <h5 class="card-title mb-1">Allocation By Clerk</h5>
                    <div class="muted">Highest allocation volume by clerk within the selected period.</div>
                </div>
                <span class="soft-chip"><i class="bi bi-people"></i> Allocation volume</span>
            </div>
            @if($allocationChart->isEmpty())
                <div class="empty-state">No allocations were recorded in the selected period.</div>
            @else
                <canvas id="faceValueStockChart" style="max-height: 360px;"></canvas>
            @endif
        </div>
    </div>

    <div class="card h-100">
        <div class="card-body">
            <h5 class="card-title mb-3">Report Notes</h5>
            <div class="glass-note mb-3">
                Parent batches show stock received directly into supervisor control.
            </div>
            <div class="glass-note mb-3">
                Allocation rows show stock sent to clerks and the supervisor balance immediately after allocation.
            </div>
            <div class="glass-note">
                Use this report with the exception report to spot clerks carrying low balances or spoilage activity.
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Supervisor Stock Batches</h5>
                <div class="muted">Received stock batches in the selected reporting window.</div>
            </div>
            <button class="btn btn-primary" type="button" onclick="exportFaceValueStockBatches()">
                <i class="bi bi-download"></i> Export Batches
            </button>
        </div>

        @if($parentBatches->isEmpty())
            <div class="empty-state">No supervisor stock batches were received in the selected period.</div>
        @else
            <div class="table-responsive table-shell">
                <table class="table table-striped datatable" id="faceValueStockBatchesTable">
                    <thead>
                        <tr>
                            <th>Batch ID</th>
                            <th>Range</th>
                            <th>Received</th>
                            <th>Balance</th>
                            <th>Date Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($parentBatches as $batch)
                            <tr>
                                <td>{{ $batch->id }}</td>
                                <td>{{ $batch->starting }} - {{ $batch->ending }}</td>
                                <td>{{ number_format((float) $batch->received, 2) }}</td>
                                <td>{{ number_format((float) $batch->balance, 2) }}</td>
                                <td>{{ optional($batch->created_at)->format('Y-m-d H:i:s') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Allocation Ledger</h5>
                <div class="muted">Detailed clerk allocations in the selected reporting window.</div>
            </div>
            <button class="btn btn-primary" type="button" onclick="exportFaceValueAllocations()">
                <i class="bi bi-download"></i> Export Allocations
            </button>
        </div>

        @if($allocationRows->isEmpty())
            <div class="empty-state">No clerk allocations were recorded in the selected period.</div>
        @else
            <div class="table-responsive table-shell">
                <table class="table table-striped datatable" id="faceValueAllocationTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Batch ID</th>
                            <th>Allocated Range</th>
                            <th>Clerk</th>
                            <th>Site</th>
                            <th>Network</th>
                            <th>Allocated</th>
                            <th>Supervisor Balance After Allocation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allocationRows as $row)
                            <tr>
                                <td>{{ $row['date'] }}</td>
                                <td>{{ $row['batch_id'] }}</td>
                                <td>{{ $row['range'] }}</td>
                                <td>{{ $row['clerk'] }}</td>
                                <td>{{ $row['site'] }}</td>
                                <td>{{ $row['network'] }}</td>
                                <td>{{ number_format($row['allocated'], 2) }}</td>
                                <td>{{ number_format($row['balance_after_allocation'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<script>
function exportFaceValueStockBatches() {
    const table = document.getElementById('faceValueStockBatchesTable');
    if (!table) {
        return;
    }

    const workbook = XLSX.utils.table_to_book(table, { sheet: 'Supervisor Stock Batches' });
    XLSX.writeFile(workbook, 'Supervisor_Face_Value_Batches.xlsx');
}

function exportFaceValueAllocations() {
    const table = document.getElementById('faceValueAllocationTable');
    if (!table) {
        return;
    }

    const workbook = XLSX.utils.table_to_book(table, { sheet: 'Allocation Ledger' });
    XLSX.writeFile(workbook, 'Supervisor_Face_Value_Allocations.xlsx');
}

document.addEventListener('DOMContentLoaded', () => {
    @if($allocationChart->isNotEmpty())
    new Chart(document.querySelector('#faceValueStockChart'), {
        type: 'bar',
        data: {
            labels: @json($allocationChart->pluck('label')->toArray()),
            datasets: [{
                label: 'Allocated Face Values',
                data: @json($allocationChart->pluck('total')->toArray()),
                backgroundColor: 'rgba(15, 107, 110, 0.82)',
                borderRadius: 12
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
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
        }
    });
    @endif
});
</script>
@endsection
