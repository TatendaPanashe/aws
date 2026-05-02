<div>
    <!-- Because you are alive, everything is possible. - Thich Nhat Hanh -->
</div>

@extends('layouts.main')

@section('title')
Welcome
@endsection

@section('content')
@include('includes.header')
@include('includes.sidebar')

<div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Premiums</div>

                    <div class="card-body">
                        <table class="table table-striped datatable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Approved By</th>
                                    <th>Agent</th>
                                    <th>Classification</th>
                                    <th>Main Agent</th>
                                    <th>Issue Date</th>
                                    <th>Status</th>
                                    <th>Reg. No</th>
                                    <th>Location</th>
                                    <th>Amount($)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($csvData as $data)
                                    <tr>
                                        <td>{{ $data->id_number }}</td>
                                        <td>{{ $data->approved }}</td>
                                        <td>{{ $data->agent }}</td>
                                        <td>{{ $data->classification }}</td>
                                        <td>{{ $data->main_agent }}</td>
                                        <td>{{ $data->issue_date }}</td>
                                        <td>{{ $data->status }}</td>
                                        <td>{{ $data->vehicle_reg_no }}</td>
                                        <td>{{ $data->location }}</td>
                                        <td>{{ $data->amount }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
</div>
@endsection