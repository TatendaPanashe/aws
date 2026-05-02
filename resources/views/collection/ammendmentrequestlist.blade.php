@extends('layouts.main')

@section('title')
    Welcome
@endsection

@section('content')
    @include('includes.header')
    @include('includes.sidebar')
<div class="container mt-4">

    <div class="card shadow-sm border-0 rounded-3">
         @if (session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Collection Amendment Requests</h5>
            <!--  -->
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Transaction ID</th>
                            <th>Currency</th>
                            <th>Bank</th>
                            <th>User</th>
                            <th>Comments</th>
                            <th>Transaction Date</th>
                            <th>Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $index => $req)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $req->transaction_id }}</td>
                                <td>{{ $req->currency }}</td>
                                <td>{{ $req->bank }}</td>
                                <td>{{ $req->user->name ?? 'N/A' }}</td>
                                <td>{{ Str::limit($req->comments, 40) }}</td>
                                <td>{{ optional($req->transaction_date)->format('Y-m-d H:i') ?? 'N/A' }}</td>
                                <td>{{ $req->created_at->format('Y-m-d H:i') }}</td>
                                <td class="text-center">
                                    <a href="{{route('dailycollection.viewrequest', $req->id)}}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                   
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    No amendment requests found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="d-flex justify-content-center mt-3">
                
            </div>
        </div>
    </div>
</div>

@endsection
