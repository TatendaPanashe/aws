@extends('layouts.main')

@section('title')
Create Budget
@endsection

@section('content')
@include('includes.header')
@include('includes.sidebar')
<div class="container mt-4">
  <h2>Add Cash In Hand Balance</h2>

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form action="{{ route('cash-in-hand-balances.store') }}" method="POST">
    @csrf
    <div class="mb-3">
      <label class="form-label">Clerk ID</label>
      <input type="number" name="clerk_id" class="form-control" value="{{ old('clerk_id') }}" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Balance</label>
      <input type="number" step="0.01" name="balance" class="form-control" value="{{ old('balance', '0.00') }}" required>
    </div>

    <button class="btn btn-success">Save</button>
    <a href="{{ route('cash-in-hand-balances.index') }}" class="btn btn-secondary">Cancel</a>
  </form>
</div>
@endsection
