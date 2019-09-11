@extends('service.layouts.app')

@section('content')
<div id="container">

    @include('service.includes.sidebar')

    <div id="main-content">
        <h1>Children's Centres</h1>
        @if (Session::get('message'))
            <div class="alert alert-success">
                {{ Session::get('message') }}
            </div>
        @endif
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>RVID Prefix</th>
                    <th>Area</th>
                    <th>Form</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($centres as $centre)
                    <tr>
                        <td>{{ $centre->name }}</td>
                        <td>{{ $centre->prefix }}</td>
                        <td>{{ $centre->sponsor->name }}</td>
                        <td>{{ ucwords($centre->print_pref) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>

@endsection
