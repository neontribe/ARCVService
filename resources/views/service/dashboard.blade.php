@extends('service.layouts.app')

@section('content')

    <div id="container">

        @include('service.includes.sidebar')

        <div id="main-content">
            <h1>Rosie Admin Dashboard</h1>

            <p>Welcome to the Rosie Admin Dashboard.</p>
                
            <p>Use the links in the sidebar to:</p>
            
            <ul>
                <li>View vouchers, add a new batch of vouchers, send vouchers and void vouchers</li>
                <li>View and pay voucher payment requests</li>
                <li>View, add and edit workers (using the 'View workers' link)</li>
                <li>View and add centres</li>
                <li>View and add areas</li>
                <li>View and add traders</li>
            </ul>
        </div>

    </div>

@endsection
