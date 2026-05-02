@extends('layouts.main')

@section('title')
Face Value Exceptions
@endsection

@section('content')
@include('includes.header')
@include('includes.sidebar')

@php
    $user = Auth::user();
    $isZINARASupervisor = ($user->role_id == 6);
    $isRegularSupervisor = ($user->role_id == 3);
    $userSBU = $resolvedSbu ?? null;
    $showSbuFilter = in_array((int) $user->role_id, [1, 3, 5, 6], true);
    $isSbuLocked = in_array((int) $user->role_id, [3, 6], true) && filled($resolvedSbu ?? null);
@endphp

<div class="pagetitle">
    <h1>Face Value Exceptions</h1>
    <p>Monitor clerks with low remaining batch balances and review spoilage activity in the selected reporting window.</p>
</div>

@if($userSBU)
    <div class="alert alert-info mb-4">
        <i class="bi bi-building"></i> <strong>Your SBU: {{ $userSBU }}</strong> - You are only viewing exception data for clerks in your SBU.
    </div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Filter Exception Report</h5>
                <div class="muted">Adjust the reporting window and low-balance threshold for exception monitoring.</div>
            </div>
            <a href="{{ route('facevalues.reports.hub') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Reports Hub
            </a>
        </div>

        <form method="GET" action="{{ route('facevalues.reports.exceptions') }}" class="row g-3">
            @if($showSbuFilter)
                <div class="col-md-3">
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
            <div class="col-md-3">
                <label for="startdate" class="form-label">Start Date</label>
                <input type="date" id="startdate" name="startdate" class="form-control" value="{{ $startDate->toDateString() }}">
            </div>
            <div class="col-md-3">
                <label for="enddate" class="form-label">End Date</label>
                <input type="date" id="enddate" name="enddate" class="form-control" value="{{ $endDate->toDateString() }}">
            </div>
            <div class="col-md-3">
                <label for="threshold" class="form-label">Low Balance Threshold</label>
                <input type="number" step="0.01" id="threshold" name="threshold" class="form-control" value="{{ number_format($threshold, 2, '.', '') }}">
            </div>
            <div class="col-12 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="{{ route('facevalues.reports.exceptions') }}" class="btn btn-secondary">Reset</a>
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

<div class="surface-grid two-up">
    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Low Balance Clerks</h5>
                    <div class="muted">Clerks whose current active stock is at or below the threshold.</div>
                </div>
                <span class="soft-chip"><i class="bi bi-exclamation-triangle"></i> Threshold alert</span>
            </div>

            @if($lowBalanceRows->isEmpty())
                <div class="empty-state">No clerks are currently below the selected threshold in your SBU.</div>
            @else
                <div class="table-responsive table-shell">
                    <table class="table table-striped datatable" id="faceValueLowBalanceTable">
                        <thead>
                            <tr>
                                <th>Clerk</th>
                                <th>Site</th>
                                <th>Network</th>
                                <th>Current Balance</th>
                                <th>Last Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowBalanceRows as $row)
                                <tr>
                                    <td>{{ $row['clerk'] }}</td>
                                    <td>{{ $row['site'] }}</td>
                                    <td>{{ $row['network'] }}</td>
                                    <td>{{ number_format($row['current_balance'], 2) }}</td>
                                    <td>{{ $row['last_activity_at'] }}</td>
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
                    <h5 class="card-title mb-1">Spoilage Register</h5>
                    <div class="muted">Entries with spoiled face values inside the selected reporting window.</div>
                </div>
                <span class="soft-chip"><i class="bi bi-exclamation-octagon"></i> Spoilage</span>
            </div>

            @if($spoiledRows->isEmpty())
                <div class="empty-state">No spoilage activity was recorded in the selected period for your SBU.</div>
            @else
                <div class="table-responsive table-shell">
                    <table class="table table-striped datatable" id="faceValueSpoilageTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Clerk</th>
                                <th>Site</th>
                                <th>Network</th>
                                <th>Batch ID</th>
                                <th>Spoiled</th>
                                <th>Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($spoiledRows as $row)
                                <tr>
                                    <td>{{ $row['date'] }}</td>
                                    <td>{{ $row['clerk'] }}</td>
                                    <td>{{ $row['site'] }}</td>
                                    <td>{{ $row['network'] }}</td>
                                    <td>{{ $row['batch_id'] }}</td>
                                    <td>{{ number_format($row['spoiled'], 2) }}</td>
                                    <td>{{ $row['comments'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
