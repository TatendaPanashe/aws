@extends('layouts.main')

@section('title')
Face Value Trace
@endsection

@section('content')
@include('includes.header')
@include('includes.sidebar')

@php
    $user = Auth::user();
    $isZINARASupervisor = ($user->role_id == 6);
    $isRegularSupervisor = ($user->role_id == 3);
    $userSBU = null;
    
    if($user->site && $user->site->sbu) {
        $userSBU = $user->site->sbu;
    } elseif($user->network && $user->network->name) {
        $userSBU = $user->network->name;
    }
    
    $toneClass = function ($tone) {
        return match ($tone) {
            'success' => 'trace-pill trace-pill--success',
            'warning' => 'trace-pill trace-pill--warning',
            'danger' => 'trace-pill trace-pill--danger',
            default => 'trace-pill trace-pill--info',
        };
    };
@endphp

@once
    <style>
        .trace-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.35rem 0.8rem;
            border-radius: 999px;
            border: 1px solid transparent;
            font-size: 0.82rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .trace-pill--info {
            background: rgba(8, 145, 178, 0.12);
            border-color: rgba(8, 145, 178, 0.18);
            color: #155e75;
        }

        .trace-pill--success {
            background: rgba(22, 101, 52, 0.12);
            border-color: rgba(22, 101, 52, 0.18);
            color: #166534;
        }

        .trace-pill--warning {
            background: rgba(180, 83, 9, 0.12);
            border-color: rgba(180, 83, 9, 0.18);
            color: #b45309;
        }

        .trace-pill--danger {
            background: rgba(185, 28, 28, 0.12);
            border-color: rgba(185, 28, 28, 0.18);
            color: #b91c1c;
        }

        .trace-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.25rem;
        }

        .trace-list {
            display: grid;
            gap: 0.85rem;
        }

        .trace-list-item {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid rgba(17, 36, 47, 0.08);
        }

        .trace-list-item:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .trace-list-item span {
            color: #5f7274;
            font-size: 0.9rem;
        }

        .trace-list-item strong {
            text-align: right;
            color: #173138;
        }

        .trace-row-match td {
            background: rgba(15, 107, 110, 0.08) !important;
        }
    </style>
@endonce

<div class="pagetitle">
    <h1>Face Value Trace</h1>
    <p>Search a face value number and follow it from supervisor stock into allocation and clerk activity.</p>
</div>

@if($userSBU)
    <div class="alert alert-info mb-4">
        <i class="bi bi-building"></i> <strong>Your SBU: {{ $userSBU }}</strong> - You can only trace face values within your SBU.
    </div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Trace Search</h5>
                <div class="muted">Enter the exact face value number captured on the stock range to locate its batch, holder, and movement trail.</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('facevalues.reports.hub') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Reports Hub
                </a>
                <a href="{{ route('facevalues.reports.stock') }}" class="btn btn-primary">
                    <i class="bi bi-box-seam"></i> Stock Report
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('facevalues.reports.trace') }}" class="row g-3">
            <div class="col-lg-9">
                <label for="face_value_number" class="form-label">Face Value Number</label>
                <input
                    type="text"
                    id="face_value_number"
                    name="face_value_number"
                    class="form-control"
                    value="{{ $searchNumber }}"
                    placeholder="Example: C80733620"
                >
            </div>
            <div class="col-lg-3 d-flex align-items-end gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">Trace Face Value</button>
                <a href="{{ route('facevalues.reports.trace') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

@if(!$traceResult && $searchNumber === '')
    <div class="empty-state">
        Search a face value number to view its origin batch, matching clerk allocation, and current traced status.
    </div>
@elseif($traceResult && !$traceResult['found'])
    <div class="empty-state">
        {{ $traceResult['message'] }}
    </div>
@elseif($traceResult && $traceResult['found'])
    <section class="metric-grid mb-4">
        @foreach($traceResult['summary_cards'] as $card)
            <article class="metric-card">
                <span class="metric-label"><i class="{{ $card['icon'] }}"></i> {{ $card['label'] }}</span>
                <strong class="metric-value">{{ $card['value'] }}</strong>
            </article>
        @endforeach
    </section>

    <div class="glass-note mb-4">
        <strong>Status:</strong>
        <span class="{{ $toneClass($traceResult['status']['tone']) }}">{{ $traceResult['status']['label'] }}</span>
        {{ $traceResult['status']['description'] }}
    </div>

    @if($traceResult['inference_notice'])
        <div class="glass-note mb-4">
            <strong>Inference note:</strong> {{ $traceResult['inference_notice'] }}
        </div>
    @endif

    <!-- Rest of the trace results remain the same -->
    <div class="trace-grid mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title mb-3">Origin Batch</h5>
                <div class="trace-list">
                    <div class="trace-list-item">
                        <span>Batch ID</span>
                        <strong>#{{ $traceResult['origin_batch']['id'] }}</strong>
                    </div>
                    <div class="trace-list-item">
                        <span>Captured Range</span>
                        <strong>{{ $traceResult['origin_batch']['range'] }}</strong>
                    </div>
                    <div class="trace-list-item">
                        <span>Supervisor</span>
                        <strong>{{ $traceResult['origin_batch']['supervisor'] }}</strong>
                    </div>
                    <div class="trace-list-item">
                        <span>Received</span>
                        <strong>{{ number_format($traceResult['origin_batch']['received'], 2) }}</strong>
                    </div>
                    <div class="trace-list-item">
                        <span>Current Balance</span>
                        <strong>{{ number_format($traceResult['origin_batch']['balance'], 2) }}</strong>
                    </div>
                    <div class="trace-list-item">
                        <span>Remaining Range</span>
                        <strong>{{ $traceResult['origin_batch']['remaining_range'] ?? 'Fully allocated' }}</strong>
                    </div>
                    <div class="trace-list-item">
                        <span>Date Added</span>
                        <strong>{{ $traceResult['origin_batch']['created_at'] }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title mb-3">Current Position</h5>
                <div class="trace-list">
                    <div class="trace-list-item">
                        <span>Searched Number</span>
                        <strong>{{ $traceResult['search'] }}</strong>
                    </div>
                    <div class="trace-list-item">
                        <span>Status</span>
                        <strong><span class="{{ $toneClass($traceResult['status']['tone']) }}">{{ $traceResult['status']['label'] }}</span></strong>
                    </div>
                    <div class="trace-list-item">
                        <span>Current Holder</span>
                        <strong>{{ $traceResult['current_holder']['label'] }}</strong>
                    </div>
                    <div class="trace-list-item">
                        <span>Holder Site</span>
                        <strong>{{ $traceResult['current_holder']['site'] }}</strong>
                    </div>
                    <div class="trace-list-item">
                        <span>Holder Network</span>
                        <strong>{{ $traceResult['current_holder']['network'] }}</strong>
                    </div>
                    <div class="trace-list-item">
                        <span>Matched Allocation</span>
                        <strong>{{ $traceResult['allocation']['range'] ?? 'Still in supervisor stock' }}</strong>
                    </div>
                    <div class="trace-list-item">
                        <span>Current Serial Window</span>
                        <strong>{{ $traceResult['current_range']['label'] ?? 'No active window' }}</strong>
                    </div>
                    <div class="trace-list-item">
                        <span>Last Movement</span>
                        <strong>{{ $traceResult['last_movement_at'] ?? 'Not captured' }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Continue with existing tables for allocation chain, clerk activity, and timeline -->
    @if($traceResult['matched_origins']->count() > 1)
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Additional Matching Origin Batches</h5>
                <div class="muted mb-3">More than one captured supervisor batch contains the searched number. The most recent match is used for the detailed trace below.</div>
                <div class="table-responsive table-shell">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Batch ID</th>
                                <th>Range</th>
                                <th>Supervisor</th>
                                <th>Date Added</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($traceResult['matched_origins'] as $origin)
                                <tr>
                                    <td>{{ $origin['batch_id'] }}</td>
                                    <td>{{ $origin['range'] }}</td>
                                    <td>{{ $origin['supervisor'] }}</td>
                                    <td>{{ $origin['created_at'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">Allocation Chain For Origin Batch</h5>
            @if($traceResult['allocation_ledger']->isEmpty())
                <div class="empty-state">This batch has not yet been allocated to any clerk.</div>
            @else
                <div class="table-responsive table-shell">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Range</th>
                                <th>Clerk</th>
                                <th>Site</th>
                                <th>Network</th>
                                <th>Allocated</th>
                                <th>Balance After</th>
                                <th>Match</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($traceResult['allocation_ledger'] as $row)
                                <tr class="{{ $row['matched'] ? 'trace-row-match' : '' }}">
                                    <td>{{ $row['date'] }}</td>
                                    <td>{{ $row['range'] }}</td>
                                    <td>{{ $row['clerk'] }}</td>
                                    <td>{{ $row['site'] }}</td>
                                    <td>{{ $row['network'] }}</td>
                                    <td>{{ number_format($row['allocated'], 2) }}</td>
                                    <td>{{ number_format($row['balance_after'], 2) }}</td>
                                    <td>
                                        @if($row['matched'])
                                            <span class="trace-pill trace-pill--success">Matched</span>
                                        @else
                                            <span class="trace-pill trace-pill--info">Other Range</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">Clerk Activity Segments</h5>
            @if($traceResult['clerk_activity_rows']->isEmpty())
                <div class="empty-state">No clerk declaration segments were found for the matched allocation yet.</div>
            @else
                <div class="table-responsive table-shell">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Stage</th>
                                <th>Derived Range</th>
                                <th>Quantity</th>
                                <th>Comments</th>
                                <th>Match</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($traceResult['clerk_activity_rows'] as $row)
                                <tr class="{{ $row['matched'] ? 'trace-row-match' : '' }}">
                                    <td>{{ $row['date'] }}</td>
                                    <td><span class="{{ $toneClass($row['tone']) }}">{{ $row['stage'] }}</span></td>
                                    <td>{{ $row['range'] }}</td>
                                    <td>{{ number_format($row['quantity'], 0) }}</td>
                                    <td>{{ $row['comments'] }}</td>
                                    <td>
                                        @if($row['matched'])
                                            <span class="trace-pill trace-pill--success">Contains Search</span>
                                        @else
                                            <span class="trace-pill trace-pill--info">No</span>
                                        @endif
                                    </td>
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
            <h5 class="card-title mb-3">Movement Timeline</h5>
            <div class="table-responsive table-shell">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Stage</th>
                            <th>Range</th>
                            <th>Quantity</th>
                            <th>Holder</th>
                            <th>Notes</th>
                            <th>Match</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($traceResult['timeline'] as $event)
                            <tr class="{{ $event['matched'] ? 'trace-row-match' : '' }}">
                                <td>{{ $event['date'] }}</td>
                                <td><span class="{{ $toneClass($event['tone']) }}">{{ $event['stage'] }}</span></td>
                                <td>{{ $event['range'] }}</td>
                                <td>{{ number_format($event['quantity'], 2) }}</td>
                                <td>{{ $event['holder'] }}</td>
                                <td>{{ $event['notes'] }}</td>
                                <td>
                                    @if($event['matched'])
                                        <span class="trace-pill trace-pill--success">Matched</span>
                                    @else
                                        <span class="trace-pill trace-pill--info">Context</span>
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