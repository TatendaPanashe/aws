@extends('layouts.main')

@section('title', 'AI Exhaustion Prediction')

@section('content')
<div class="container-fluid px-0">
    <!-- Page Header -->
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="h3 mb-2" style="background: linear-gradient(135deg, #10b981, #3b82f6); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    AI Exhaustion Prediction
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}" style="color: #10b981;">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('supervisorfacevalues.index') }}" style="color: #10b981;">Face Values</a></li>
                        <li class="breadcrumb-item active" style="color: #6b7280;">Prediction</li>
                    </ol>
                </nav>
                <p class="text-muted mt-2 mb-0">AI-powered predictions for face value batch exhaustion</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('supervisorfacevalues.index') }}" class="btn-outline-modern">
                    <i class="bi bi-arrow-left me-2"></i>Back to Face Values
                </a>
            </div>
        </div>
    </div>

    <!-- Prediction Results Card -->
    <div class="modern-card">
        <div class="card-header bg-transparent border-bottom p-4" style="border-color: rgba(16, 185, 129, 0.15);">
            <div class="d-flex align-items-center gap-2">
                <div class="stat-icon-sm">
                    <i class="bi bi-calendar-check" style="color: #10b981;"></i>
                </div>
                <h5 class="mb-0 fw-semibold">Exhaustion Forecast</h5>
                <span class="badge ms-2" style="background: linear-gradient(135deg, #10b981, #3b82f6);">Powered by AI</span>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-info mb-4">
                <i class="bi bi-info-circle-fill me-2"></i>
                This prediction is based on usage patterns from the last 30 days. Actual results may vary.
            </div>
            
            <div class="prediction-content bg-light p-4 rounded" style="white-space: pre-wrap; line-height: 1.8;">
                {!! nl2br(e($prediction)) !!}
            </div>
            
            <div class="mt-4 d-flex gap-3">
                <button onclick="window.print()" class="btn-outline-modern">
                    <i class="bi bi-printer me-1"></i>Print Report
                </button>
                <a href="{{ route('supervisorfacevalues.index') }}" class="btn-outline-modern">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>
    </div>

    <!-- Current Batches Data Card -->
    <div class="modern-card mt-4">
        <div class="card-header bg-transparent border-bottom p-4" style="border-color: rgba(16, 185, 129, 0.15);">
            <h5 class="mb-0 fw-semibold">
                <i class="bi bi-box-seam me-2" style="color: #10b981;"></i>
                Current Batches Status
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern mb-0">
                    <thead>
                        <tr>
                            <th>Batch ID</th>
                            <th>Range</th>
                            <th>Remaining</th>
                            <th>Daily Usage</th>
                            <th>Days Until Exhaustion</th>
                            <th>Status</th>
                        </thead>
                        <tbody>
                            @foreach($batches as $batch)
                            
                                <td class="fw-medium">#{{ $batch['id'] }}
                                
                                <td><code>{{ $batch['range'] }}</code>
                                
                                <td class="fw-semibold">{{ number_format($batch['remaining']) }}
                                
                                <td>{{ number_format($batch['daily_usage'], 2) }} per day
                                
                                <td>
                                    @php
                                        $daysLeft = $batch['daily_usage'] > 0 ? ceil($batch['remaining'] / $batch['daily_usage']) : '∞';
                                    @endphp
                                    @if($daysLeft !== '∞')
                                        @if($daysLeft <= 7)
                                            <span class="text-danger fw-semibold">{{ $daysLeft }} days</span>
                                        @elseif($daysLeft <= 30)
                                            <span class="text-warning fw-semibold">{{ $daysLeft }} days</span>
                                        @else
                                            <span class="text-success">{{ $daysLeft }} days</span>
                                        @endif
                                    @else
                                        <span class="text-muted">No usage data</span>
                                    @endif
                                
                                
                                <td>
                                    @if($daysLeft !== '∞')
                                        @if($daysLeft <= 7)
                                            <span class="status-badge status-critical">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Critical
                                            </span>
                                        @elseif($daysLeft <= 30)
                                            <span class="status-badge status-warning">
                                                <i class="bi bi-clock me-1"></i>Low Stock
                                            </span>
                                        @else
                                            <span class="status-badge status-good">
                                                <i class="bi bi-check-circle me-1"></i>OK
                                            </span>
                                        @endif
                                    @else
                                        <span class="status-badge status-unknown">
                                            <i class="bi bi-question-circle me-1"></i>Unknown
                                        </span>
                                    @endif
                                
                            
                            @endforeach
                        </tbody>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .stat-icon-sm {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(59, 130, 246, 0.1));
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .prediction-content {
        background: linear-gradient(135deg, #f9fafb, #ffffff);
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 2rem;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .status-critical {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
        color: #dc2626;
    }
    
    .status-warning {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05));
        color: #f59e0b;
    }
    
    .status-good {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
        color: #10b981;
    }
    
    .status-unknown {
        background: linear-gradient(135deg, rgba(107, 114, 128, 0.1), rgba(107, 114, 128, 0.05));
        color: #6b7280;
    }
    
    @media print {
        .btn-outline-modern, .alert-info {
            display: none;
        }
        
        .prediction-content {
            background: white;
            border: none;
        }
    }
</style>
@endpush
@endsection