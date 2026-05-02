@extends('layouts.main')

@section('title')
{{ $chartTitle }}
@endsection

@section('content')
@include('includes.header')
@include('includes.sidebar')

@php
    $chartHeight = max(560, ($comparisonRows->count() * 56) + 120);
@endphp

<div class="pagetitle">
    <h1>{{ $chartTitle }}</h1>
    <p>{{ $chartDescription }} Active period: {{ $periodLabel }}.</p>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Filter Chart Scope</h5>
                <div class="muted">Choose the month, year, view mode, and optional network for this chart page.</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('budgets.index', ['year' => $year, 'month' => $month, 'network_id' => $networkId, 'view_mode' => $viewMode]) }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Budget Review
                </a>
                @if($currency === 'usd')
                    <a href="{{ route('budgets.charts.zwg', ['year' => $year, 'month' => $month, 'network_id' => $networkId, 'view_mode' => $viewMode]) }}" class="btn btn-primary">
                        <i class="bi bi-wallet2"></i> ZWG Chart Page
                    </a>
                @else
                    <a href="{{ route('budgets.charts.usd', ['year' => $year, 'month' => $month, 'network_id' => $networkId, 'view_mode' => $viewMode]) }}" class="btn btn-primary">
                        <i class="bi bi-cash-stack"></i> USD Chart Page
                    </a>
                @endif
            </div>
        </div>

        <form class="row g-3" method="get" action="{{ $currency === 'usd' ? route('budgets.charts.usd') : route('budgets.charts.zwg') }}">
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
                <a href="{{ $currency === 'usd' ? route('budgets.charts.usd') : route('budgets.charts.zwg') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

@if ($comparisonRows->isEmpty())
    <div class="empty-state">
        No site budgets or actual collections were found for {{ $periodLabel }} in the selected scope.
    </div>
@else
    <section class="metric-grid mb-4">
        @foreach($summaryCards as $card)
            <article class="metric-card">
                <span class="metric-label"><i class="{{ $card['icon'] }}"></i> {{ $card['label'] }}</span>
                <strong class="metric-value">{{ $card['value'] }}</strong>
                <div class="metric-note">{{ $card['note'] }}</div>
            </article>
        @endforeach
    </section>

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">{{ $chartTitle }}</h5>
                    <div class="muted">{{ $periodLabel }} comparison by site.</div>
                </div>
                <span class="soft-chip"><i class="bi bi-bar-chart-line"></i> Horizontal comparison</span>
            </div>

            <div class="d-flex gap-3 flex-wrap mb-3">
                <div class="soft-chip">
                    <span class="d-inline-block rounded-circle me-2" style="width: 12px; height: 12px; background: {{ $budgetColor }};"></span>
                    Key: {{ $budgetLabel }}
                </div>
                <div class="soft-chip">
                    <span class="d-inline-block rounded-circle me-2" style="width: 12px; height: 12px; background: {{ $actualColor }};"></span>
                    Key: {{ $actualLabel }}
                </div>
            </div>

            <div style="position: relative; height: {{ $chartHeight }}px;">
                <canvas id="{{ $chartId }}"></canvas>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="card-title mb-1">Site Breakdown</h5>
                    <div class="muted">Detailed values backing the chart.</div>
                </div>
                <span class="soft-chip"><i class="bi bi-table"></i> Comparison table</span>
            </div>

            <div class="table-responsive table-shell">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Site</th>
                            <th>Network</th>
                            <th>{{ $budgetLabel }}</th>
                            <th>{{ $actualLabel }}</th>
                            <th>Variance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comparisonRows as $row)
                            <tr>
                                <td>{{ $row['site_name'] }}</td>
                                <td>{{ $row['network_name'] }}</td>
                                @if ($currency === 'usd')
                                    <td>${{ number_format($row['budgeted_amount_usd'], 2) }}</td>
                                    <td>${{ number_format($row['actual_usd'], 2) }}</td>
                                    <td class="{{ $row[$varianceKey] >= 0 ? 'text-success' : 'text-danger' }}">
                                        ${{ number_format($row[$varianceKey], 2) }}
                                    </td>
                                @else
                                    <td>ZWG {{ number_format($row['budgeted_amount_zwg'], 2) }}</td>
                                    <td>ZWG {{ number_format($row['actual_zwg'], 2) }}</td>
                                    <td class="{{ $row[$varianceKey] >= 0 ? 'text-success' : 'text-danger' }}">
                                        ZWG {{ number_format($row[$varianceKey], 2) }}
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        if (!document.querySelector('#{{ $chartId }}')) {
            return;
        }

        new Chart(document.querySelector('#{{ $chartId }}'), {
            type: 'bar',
            data: {
                labels: @json($chartData['labels']),
                datasets: [
                    {
                        label: @json($budgetLabel),
                        data: @json($chartData['budget']),
                        backgroundColor: @json($budgetColor),
                        borderRadius: 12
                    },
                    {
                        label: @json($actualLabel),
                        data: @json($chartData['actual']),
                        backgroundColor: @json($actualColor),
                        borderRadius: 12
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(17, 36, 47, 0.08)'
                        },
                        ticks: {
                            color: '#5f7274'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            autoSkip: false,
                            color: '#5f7274',
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    });
    </script>
@endif
@endsection
