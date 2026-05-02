@extends('layouts.main')

@section('title')
    Collection Amendment Approval
@endsection

@section('content')
    @include('includes.header')
    @include('includes.sidebar')

<div class="container mt-4">
     @if (session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap">
    <div>
        <h5 class="mb-1">Collection Amendment Comparison — #{{ $ammendment->transaction_id }}</h5>
        <h6 class="mb-1">Amendment Date: {{ \Carbon\Carbon::parse($ammendment->ammendmentdate)->format('d M Y H:i') }}</h6>
        <h6 class="mb-0">Submitted By: {{ $ammendment->user->name }}</h6>
        <h6 class="mb-0">Submitted On: {{ $ammendment->transaction_date }}</h6>
    </div>
    <a href="{{ route('dailycollection.ammendmentrequestlist') }}" class="btn btn-light btn-sm mt-2 mt-md-0">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>


        <div class="card-body">
            <p class="text-muted mb-3">
                Review the old and new values for this transaction amendment before approval.
            </p>

            <form action="{{ route('dailycollection.approveammendmentrequest') }}" method="POST">
                @csrf
                <input type="hidden" name="transaction_id" value="{{ $ammendment->transaction_id }}">
                <input type="hidden" name="ammendment_id" value="{{ $ammendment->id }}">
                <input type="hidden" name="userid" value="{{ auth()->user()->id }}">

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:25%">Field</th>
                                <th class="text-danger">Old Value</th>
                                <th class="text-success">New Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $fields = [
                                    'currency',
                                    'third_party_premiums',
                                    'full_cover_premiums',
                                    'zinara_fees',
                                    'usd_mpos',
                                    'zwg_mpos',
                                    'zwg_third_party_premiums',
                                    'zwg_full_cover_premiums',
                                    'zwg_zinara_fees',
                                    'usd_total_deposited',
                                    'zwg_total_deposited',
                                    'usd_cash',
                                    'usd_swipe',
                                    'usd_transfers',
                                    'zwg_cash',
                                    'zwg_swipe',
                                    'zwg_transfers',
                                    'bank',
                                     'usd_total_deposited',
                                     'zwg_total_deposited',
                                    'insurance_transactions',
                                    'zwg_insurance_transactions',
                                    'zinara_transactions',
                                   // 'username',
                                    //'networkid',
                                    //'siteid',
                                    //'balance',
                                    //'code',
                                    //'site_name',
                                    'zwg_debit_sales',
                                    'zwg_credit_sales',
                                    'usd_debit_sales',
                                    
                                    'usd_credit_sales',
                                    'comments',
                                  //  'user_id'
                                ];
                            @endphp

                            @foreach ($fields as $field)
                                @php
                                    $oldField = $field . 'old';
                                    $oldValue = $ammendment->$oldField ?? '';
                                    $newValue = $ammendment->$field ?? '';
                                    $changed = $oldValue != $newValue;
                                @endphp
                                <tr @if($changed) class="table-warning" @endif>
                                    <td><strong>{{ ucwords(str_replace('_', ' ', $field)) }}</strong></td>
                                    <td class="text-danger">{{ $oldValue !== '' ? $oldValue : '-' }}</td>
                                    <td class="text-success">
                                        <input type="text" 
                                               name="{{ $field }}" 
                                               class="form-control form-control-sm bg-light @if($changed) border-success @endif" 
                                               value="{{ $newValue }}" 
                                               readonly>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-success me-2">
                        <i class="bi bi-check-circle"></i> Approve Amendment
                    </button>
                    <a href="{{ route('dailycollection.ammendmentrequestlist') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
