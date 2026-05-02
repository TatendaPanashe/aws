@extends('layouts.main')

@section('title')
Create Budget
@endsection

@section('content')
@include('includes.header')
@include('includes.sidebar')
<div class="container mt-4">
  <h2>View Cash In Hand Balance</h2>

  <div class="card">
    <div class="card-body">
      <p><strong>ID:</strong> {{ $balance->id }}</p>
      <p><strong>Clerk ID:</strong> {{ $balance->clerk_id }}</p>
      <p><strong>Balance:</strong> {{ number_format($balance->balance, 2) }}</p>
      <p><strong>Created:</strong> {{ $balance->created_at }}</p>
      <p><strong>Updated:</strong> {{ $balance->updated_at }}</p>

      <a href="{{ route('ch.edit', $balance->id) }}" class="btn btn-warning">Edit</a>
      <a href="{{ route('cash-in-hand-balances.index') }}" class="btn btn-secondary">Back</a>
    </div>
  </div>
</div>
@endsection
