@extends('layouts.main')

@section('title')
Edit Site Budget
@endsection

@section('content')
@include('includes.header')
@include('includes.sidebar')

<div class="pagetitle">
    <h1>Edit Site Budget</h1>
    <p>Adjust a saved monthly target for a site and compare it against the recorded actuals for that month.</p>
</div>

<div class="surface-grid two-up">
    <div class="card h-100">
        <div class="card-body">
            <h5 class="card-title">Budget Details</h5>
            <form action="{{ route('budgets.update', $budget->id) }}" method="POST" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-md-6">
                    <label for="year" class="form-label">Year</label>
                    <select id="year" name="year" class="form-select @error('year') is-invalid @enderror">
                        @for ($y = Carbon\Carbon::now()->year - 2; $y <= Carbon\Carbon::now()->year + 5; $y++)
                            <option value="{{ $y }}" {{ old('year', $budget->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                    @error('year')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="month" class="form-label">Month</label>
                    <select id="month" name="month" class="form-select @error('month') is-invalid @enderror">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ old('month', $budget->month) == $m ? 'selected' : '' }}>
                                {{ Carbon\Carbon::create(null, $m, 1)->format('F') }}
                            </option>
                        @endfor
                    </select>
                    @error('month')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <label for="site_id" class="form-label">Site</label>
                    <select id="site_id" name="site_id" class="form-select @error('site_id') is-invalid @enderror">
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}" {{ old('site_id', $budget->site_id) == $site->id ? 'selected' : '' }}>
                                {{ $site->site_name }}{{ $site->network ? ' - ' . $site->network->name : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('site_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="budgeted_amount_usd" class="form-label">Budgeted Amount (USD)</label>
                    <input type="number" step="0.01" class="form-control @error('budgeted_amount_usd') is-invalid @enderror" id="budgeted_amount_usd" name="budgeted_amount_usd" value="{{ old('budgeted_amount_usd', $budget->budgeted_amount_usd) }}">
                    @error('budgeted_amount_usd')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="budgeted_amount_zwg" class="form-label">Budgeted Amount (ZWG)</label>
                    <input type="number" step="0.01" class="form-control @error('budgeted_amount_zwg') is-invalid @enderror" id="budgeted_amount_zwg" name="budgeted_amount_zwg" value="{{ old('budgeted_amount_zwg', $budget->budgeted_amount_zwg) }}">
                    @error('budgeted_amount_zwg')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary">Update Budget</button>
                    <a href="{{ route('budgets.index', ['year' => $budget->year, 'month' => $budget->month, 'network_id' => $budget->network_id]) }}" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card h-100">
        <div class="card-body">
            <h5 class="card-title">Current Comparison</h5>
            <div class="glass-note mb-3">
                <strong>Site</strong><br>
                {{ optional($budget->site)->site_name ?? 'Unknown site' }}
            </div>
            <div class="glass-note mb-3">
                <strong>Network</strong><br>
                {{ optional(optional($budget->site)->network)->name ?? 'Unassigned' }}
            </div>
            <div class="glass-note mb-3">
                <strong>Actual USD</strong><br>
                ${{ number_format((float) ($currentActuals->actual_usd ?? 0), 2) }}
            </div>
            <div class="glass-note">
                <strong>Actual ZWG</strong><br>
                ZWG {{ number_format((float) ($currentActuals->actual_zwg ?? 0), 2) }}
            </div>
        </div>
    </div>
</div>

@endsection
