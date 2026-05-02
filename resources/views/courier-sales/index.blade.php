@extends('layouts.main')

@section('title', 'Courier Connect Sales')

@section('content')
@include('includes.header')
@include('includes.sidebar')

<div class="pagetitle">
    <h1>Courier Connect Sales</h1>
    <p>Capture Courier Connect sales separately from normal face value declarations so supervisors can compare Courier document sales against the usual Nicoz Diamond and Champions activity.</p>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<section class="metric-grid mb-4">
    @foreach($summaryCards as $card)
        <article class="metric-card">
            <span class="metric-label"><i class="{{ $card['icon'] }}"></i> {{ $card['label'] }}</span>
            <strong class="metric-value">{{ $card['value'] }}</strong>
            <div class="metric-note">{{ $card['note'] }}</div>
        </article>
    @endforeach
</section>

@if($isCourierClerk)
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Submit Courier Sale</h5>
            <div class="glass-note mb-3">
                Record the insurer and sales amount made using the Courier Connect face value document. Face value usage itself is still declared from the normal face value declaration screen.
            </div>

            @if($batches->isEmpty())
                <div class="alert alert-warning mb-0">
                    No active face value batches are available for your account. Ask your supervisor to allocate Courier stock before posting Courier sales.
                </div>
            @else
                <form method="POST" action="{{ route('courier.sales.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label for="face_value_id" class="form-label">Face Value Batch</label>
                        <select id="face_value_id" name="face_value_id" class="form-select" required>
                            <option value="">Choose batch...</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}" data-batch="{{ $batch->batch_id }}" {{ (string) old('face_value_id') === (string) $batch->id ? 'selected' : '' }}>
                                    #{{ $batch->batch_id }} | {{ $batch->starting }} - {{ $batch->ending }} | Balance {{ number_format((float) $batch->batch_balance, 0) }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" id="batch_id" name="batch_id" value="{{ old('batch_id') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="insurance_provider" class="form-label">Insurance</label>
                        <select id="insurance_provider" name="insurance_provider" class="form-select" required>
                            <option value="">Choose...</option>
                            @foreach($insuranceProviders as $provider)
                                <option value="{{ $provider }}" {{ old('insurance_provider') === $provider ? 'selected' : '' }}>{{ $provider }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="currency" class="form-label">Currency</label>
                        <select id="currency" name="currency" class="form-select" required>
                            @foreach($currencies as $currency)
                                <option value="{{ $currency }}" {{ old('currency', 'USD') === $currency ? 'selected' : '' }}>{{ $currency }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="sales_amount" class="form-label">Sales Amount</label>
                        <input type="number" min="0.01" step="0.01" id="sales_amount" name="sales_amount" class="form-control" value="{{ old('sales_amount') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label for="sale_date" class="form-label">Sale Date</label>
                        <input type="date" id="sale_date" name="sale_date" class="form-control" value="{{ old('sale_date', $selectedDate) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label for="comments" class="form-label">Comment</label>
                        <input type="text" id="comments" name="comments" class="form-control" value="{{ old('comments') }}" placeholder="Optional reconciliation note">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Submit Courier Sale</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h5 class="card-title mb-1">Courier Sales Register</h5>
                <div class="muted">Review Courier Connect sales by insurer, batch, clerk, and currency.</div>
            </div>
        </div>

        <form method="GET" action="{{ route('courier.sales.index') }}" class="row g-3 mb-3">
            <div class="col-md-4">
                <label for="sale_date_filter" class="form-label">Sale Date</label>
                <input type="date" id="sale_date_filter" name="sale_date" class="form-control" value="{{ request('sale_date', $selectedDate) }}">
            </div>
            <div class="col-md-4">
                <label for="provider_filter" class="form-label">Insurance</label>
                <select id="provider_filter" name="insurance_provider" class="form-select">
                    <option value="">All insurers</option>
                    @foreach($insuranceProviders as $provider)
                        <option value="{{ $provider }}" {{ $selectedProvider === $provider ? 'selected' : '' }}>{{ $provider }}</option>
                    @endforeach
                </select>
            </div>
            @if($clerks->isNotEmpty())
                <div class="col-md-4">
                    <label for="clerk_id" class="form-label">Clerk</label>
                    <select id="clerk_id" name="clerk_id" class="form-select">
                        <option value="">All clerks</option>
                        @foreach($clerks as $clerk)
                            <option value="{{ $clerk->id }}" {{ (string) $selectedClerkId === (string) $clerk->id ? 'selected' : '' }}>
                                {{ trim($clerk->name . ' ' . ($clerk->surname ?? '')) }}{{ $clerk->site ? ' - ' . $clerk->site->site_name : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-12 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="{{ route('courier.sales.index') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>

        @if($sales->isEmpty())
            <div class="empty-state">No Courier Connect sales were found for the current selection.</div>
        @else
            <div class="table-responsive table-shell">
                <table class="table table-striped datatable" id="courierSalesTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Clerk</th>
                            <th>Site</th>
                            <th>Insurance</th>
                            <th>Currency</th>
                            <th>Sales Amount</th>
                            <th>Batch ID</th>
                            <th>Batch Range</th>
                            <th>Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sales as $sale)
                            <tr>
                                <td>{{ optional($sale->sale_date)->toDateString() }}</td>
                                <td>{{ trim(($sale->clerk->name ?? 'Unknown') . ' ' . ($sale->clerk->surname ?? '')) }}</td>
                                <td>{{ $sale->clerk?->site?->site_name ?? 'N/A' }}</td>
                                <td>{{ $sale->insurance_provider }}</td>
                                <td>{{ $sale->currency }}</td>
                                <td>{{ number_format((float) $sale->sales_amount, 2) }}</td>
                                <td>{{ $sale->batch_id }}</td>
                                <td>{{ $sale->faceValue?->starting ?? 'N/A' }} - {{ $sale->faceValue?->ending ?? 'N/A' }}</td>
                                <td>{{ $sale->comments ?: 'No comment' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const batchSelect = document.getElementById('face_value_id');
    const batchIdField = document.getElementById('batch_id');

    if (!batchSelect || !batchIdField) {
        return;
    }

    function syncBatchId() {
        const selectedOption = batchSelect.options[batchSelect.selectedIndex];
        batchIdField.value = selectedOption ? (selectedOption.dataset.batch || '') : '';
    }

    batchSelect.addEventListener('change', syncBatchId);
    syncBatchId();
});
</script>
@endsection
