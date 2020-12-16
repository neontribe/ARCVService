@extends('service.layouts.app')

@section('content')
<div id="container">

    @include('service.includes.sidebar')

    <div id="main-content">
        <h1>Markets</h1>
        @if (Session::get('message'))
            <div class="alert alert-success">
                {{ Session::get('message') }}
            </div>
        @endif
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Area</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($markets as $market)
                    <tr>
                        <td>{{ $market->name }}</td>
                        <td>{{ $market->sponsor->name }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>

@endsection
