@extends('layouts.main')


@section('title')
Welcome
@endsection



@section('content')
@include('includes.header')
@include('includes.sidebar')
<section class="section">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title">All Networks</h5>
                <a href="{{ route('networks.create')}}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Add Network
                </a>
                </div>

                <!-- Table with stripped rows -->
                <table class="table table-striped">
                <thead>
                    <tr>
                    <th scope="col">Name</th>
                    <!--<th scope="col">City</th>
                    <th scope="col">Province</th>-->
                    <th scope="col">Description</th>
                    <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($networks as $network)
                    <tr>
                       
                    <td>{{ $network->name }}</td>
                    <!--<td>{{ $network->city }}</td>
                    <td>{{ $network->province }}</td>-->
                    <td>{{ $network->description }}</td>
                    
                    <td>{{ $network->user->name }}</td>
                    <td>
                        <a href="{{ route('networks.show', $network) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('networks.edit', $network) }}" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil"></i>
                        </a>
                        <form action="{{ route('destroynetwork') }}" method="POST" class="d-inline">
                        @csrf
                           
                        <input type="hidden" name="id" value="{{ $network->id }}">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">
                            <i class="bi bi-trash"></i>
                        </button>
                        </form>
                    </td>
                    </tr>
                    @endforeach
                </tbody>
                </table>
                <!-- End Table with stripped rows -->
            </div>
            </div>
        </div>
    </div>
</section>

        @endsection