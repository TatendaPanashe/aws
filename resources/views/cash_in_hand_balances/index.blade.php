@extends('layouts.main')

@section('title')
Cash In Hand Balances
@endsection

@section('content')
@include('includes.header')
@include('includes.sidebar')

<div class="container mt-4">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Cash In Hand Balances</h2>
  </div>

  @if ($message = Session::get('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ $message }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="card">
    <div class="card-body">

      <div class="table-responsive">
        <table id="balancesTable" class="table table-striped table-bordered datatable">
          <thead class="table-dark">
            <tr>
              <th>#</th>
              <th>Clerk ID</th>
              <th>Balance ZWG</th>
              <th>Balance USD</th>
              <th>Created</th>
              <th width="160px">Action</th>
            </tr>
          </thead>

          <tbody>
            @foreach($balances as $b)
              <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $b->clerk->name }}</td>
                <td>{{ number_format($b->balance_zwg, 2) }}</td>
                <td>{{ number_format($b->balance_usd, 2) }}</td>
                <td>{{ $b->created_at->format('Y-m-d') }}</td>
                <td>
                  <a href="{{ route('cash-in-hand-balances.show', $b->id) }}" class="btn btn-sm btn-info">View</a>
                  <a href="{{ route('cash-in-hand-balances.edit', $b->id) }}" class="btn btn-sm btn-warning">Edit</a>

                  <form action="{{ route('cash-in-hand-balances.destroy', $b->id) }}"
                        method="POST"
                        class="d-inline"
                        onsubmit="return confirm('Delete this record?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-danger">Delete</button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>

        </table>
      </div>

    </div>
  </div>

</div>
@endsection

