@extends('service.layouts.app')

@section('content')
<div id="container">

    @include('service.includes.sidebar')

    <div id="main-content">
        <h1>Areas</h1>
        @if (Session::get('message'))
            <div class="alert alert-success">
                {{ Session::get('message') }}
            </div>
        @endif
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Voucher Prefix</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sponsors as $sponsor)
                    <tr>
                        <td>{{ $sponsor->name }}</td>
                        <td>{{ $sponsor->shortcode }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>

@endsection
