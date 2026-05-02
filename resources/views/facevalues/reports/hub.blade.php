@extends('layouts.main')

@section('title')
Face Value Reports
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
@endphp

<div class="pagetitle">
    <h1>Face Value Reports</h1>
    <p>
        @if($isZINARASupervisor)
            ZINARA Supervisor reporting for face value stock, clerk allocations, batch movement, and operational exceptions.
        @elseif($isRegularSupervisor && $userSBU)
            Supervisor reporting for SBU {{ $userSBU }} face value stock, clerk allocations, batch movement, and operational exceptions.
        @else
            Supervisor reporting for face value stock, clerk allocations, batch movement, and operational exceptions.
        @endif
    </p>
</div>

@if($userSBU)
    <div class="alert alert-info mb-4">
        <i class="bi bi-building"></i> <strong>Your SBU: {{ $userSBU }}</strong> - You are only viewing data for clerks in your SBU.
    </div>
@endif

<section class="section-hero mb-4">
    <div>
        <div class="hero-spotlight">ZINARA Reporting</div>
        <h2>Face value stock control, clerk usage, and exception monitoring from one reporting hub.</h2>
        <p>
            Start here to review stock received, stock allocated, current balances, and issue-focused reports for low balance and spoilage.
        </p>
    </div>

    <div class="hero-side-panel">
        <div class="hero-side-card">
            <span>Stock Overview</span>
            <strong>{{ $summaryCards[0]['value'] ?? '0' }}</strong>
        </div>
        <div class="hero-side-card">
            <span>Active Clerks</span>
            <strong>{{ $summaryCards[3]['value'] ?? '0' }}</strong>
        </div>
        <div class="hero-side-card">
            <span>Open Batches</span>
            <strong>{{ $summaryCards[4]['value'] ?? '0' }}</strong>
        </div>
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

<div class="surface-grid two-up mb-4">
    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Top Clerk Allocations</h5>
                    <div class="muted">Clerks who received the highest allocated face value volume.</div>
                </div>
                <span class="soft-chip"><i class="bi bi-bar-chart-line"></i> Allocation focus</span>
            </div>
            @if($allocationByClerk->isEmpty())
                <div class="empty-state">No allocation data is available yet for your SBU.</div>
            @else
                <canvas id="faceValueAllocationChart" style="max-height: 360px;"></canvas>
            @endif
        </div>
    </div>

    <div class="card h-100">
        <div class="card-body">
            <h5 class="card-title mb-3">Report Shortcuts</h5>
            <div class="quick-action-grid">
                @foreach($reportCards as $report)
                    <a href="{{ $report['route'] }}" class="quick-action-card text-decoration-none">
                        <div class="quick-action-icon"><i class="{{ $report['icon'] }}"></i></div>
                        <h3>{{ $report['title'] }}</h3>
                        <p>{{ $report['description'] }}</p>
                        <span class="soft-chip">{{ $report['chip'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

@if($allocationByClerk->isNotEmpty())
<script>
document.addEventListener('DOMContentLoaded', () => {
    new Chart(document.querySelector('#faceValueAllocationChart'), {
        type: 'bar',
        data: {
            labels: @json($allocationByClerk->pluck('label')->toArray()),
            datasets: [{
                label: 'Allocated Face Values',
                data: @json($allocationByClerk->pluck('total')->toArray()),
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
});
</script>
@endif
@endsection