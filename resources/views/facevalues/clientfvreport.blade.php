@extends('layouts.main')

@section('title')
Daily Face Value Entries
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
    <h1>Daily Face Value Entries</h1>
    <p>Inspect clerk-level ZINARA face value activity for a selected day, including allocations, declarations, spoilage, and batch balances.</p>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Filter Daily Entries</h5>
                <div class="muted">Choose a reporting date and optionally focus on one clerk.</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('facevalues.reports.hub') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Reports Hub
                </a>
                <a href="{{ route('facevalues.reports.exceptions') }}" class="btn btn-primary">
                    <i class="bi bi-clipboard2-pulse"></i> Exception Report
                </a>
            </div>
        </div>

        <div class="glass-note mb-3">
            Parent allocation rows show stock handed to clerks, while declaration rows show usage, spoilage, and current batch balance updates.
        </div>

        <form method="GET" action="{{ route('clientfvreport') }}" class="row g-3">
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
                <label for="date" class="form-label">Date</label>
                <input type="date" id="date" name="date" class="form-control" value="{{ $date->toDateString() }}">
            </div>
            <div class="col-md-4">
                <label for="clerk_id" class="form-label">Clerk</label>
                <select id="clerk_id" name="clerk_id" class="form-select">
                    <option value="">All Clerks</option>
                    @foreach ($clerks as $clerk)
                        <option value="{{ $clerk->id }}" {{ (string) $selectedClerkId === (string) $clerk->id ? 'selected' : '' }}>
                            {{ trim($clerk->name . ' ' . ($clerk->surname ?? '')) }}{{ $clerk->site ? ' - ' . $clerk->site->site_name : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="{{ route('clientfvreport') }}" class="btn btn-secondary">Reset</a>
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

@if($docs->isEmpty())
    <div class="empty-state">
        No face value activity was captured for {{ $date->toDateString() }} in the selected scope.
    </div>
@else
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Daily Entry Register</h5>
                    <div class="muted">{{ $date->toFormattedDateString() }} detailed face value movement.</div>
                </div>
                <button class="btn btn-primary" type="button" onclick="exportFaceValueDailyEntries()">
                    <i class="bi bi-download"></i> Export to Excel
                </button>
            </div>

            <div class="table-responsive table-shell">
                <table class="table table-striped datatable" id="faceValueDailyEntriesTable">
                    <thead>
                        <tr>
                            <th>Clerk</th>
                            <th>Site</th>
                            <th>Network</th>
                            <th>SBU</th>
                            <th>Date & Time</th>
                            <th>Entry Type</th>
                            <th>Range</th>
                            <th>Opening Balance</th>
                            <th>Received</th>
                            <th>Used</th>
                            <th>Spoiled</th>
                            <th>Closing Balance</th>
                            <th>Batch Balance</th>
                            <th>Batch ID</th>
                            <th>Insurance</th>
                            <th>Channel</th>
                            <th>Comments</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($docs as $data)
                            <tr>
                                <td>{{ $data['client'] }}</td>
                                <td>{{ $data['siteid'] }}</td>
                                <td>{{ $data['network'] }}</td>
                                <td>{{ $data['SBU'] }}</td>
                                <td>{{ $data['date'] }}</td>
                                <td>
                                    <span class="soft-chip">{{ $data['is_parent'] ? 'Parent Allocation' : 'Declaration' }}</span>
                                </td>
                                <td>{{ $data['starting_range'] }} - {{ $data['ending_range'] }}</td>
                                <td>{{ number_format($data['opening_balance'], 2) }}</td>
                                <td>{{ number_format($data['received'], 2) }}</td>
                                <td>{{ number_format($data['used'], 2) }}</td>
                                <td>{{ number_format($data['spoiled'], 2) }}</td>
                                <td>{{ number_format($data['closing_balance'], 2) }}</td>
                                <td>{{ number_format($data['batch_balance'], 2) }}</td>
                                <td>{{ $data['batch_id'] ?? 'N/A' }}</td>
                                <td>{{ $data['insurance_provider'] ?: 'N/A' }}</td>
                                <td>{{ $data['document_channel'] ?: 'Standard' }}</td>
                                <td>{{ $data['comments'] ?? 'No comment' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="8">Totals</th>
                            <th>{{ number_format($docs->sum('received'), 2) }}</th>
                            <th>{{ number_format($docs->sum('used'), 2) }}</th>
                            <th>{{ number_format($docs->sum('spoiled'), 2) }}</th>
                            <th>{{ number_format($docs->sum('closing_balance'), 2) }}</th>
                            <th>{{ number_format($docs->sum('batch_balance'), 2) }}</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <script>
    function exportFaceValueDailyEntries() {
        const table = document.getElementById('faceValueDailyEntriesTable');
        if (!table) {
            return;
        }

        const workbook = XLSX.utils.table_to_book(table, { sheet: 'Daily Face Value Entries' });
        XLSX.writeFile(workbook, 'Daily_Face_Value_Entries.xlsx');
    }
    </script>
@endif
@endsection
