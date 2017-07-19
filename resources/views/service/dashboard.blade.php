@extends('service.layouts.app')

@section('content')
<div id="container">

    <div id="left">

        <ul>
            <li>Service portal</li>
            <li><a href="{{ url('/') }}"><span class="glyphicon glyphicon-home"></span>Dashboard</a></li>
            <li><a href="{{ url('/') }}"><span class="glyphicon glyphicon-plus"></span>Add vouchers</a></li>
            <li><a href="{{ url('/') }}"><span class="glyphicon glyphicon-th-list"></span>View added vouchers</a></li>

            @unless(Config('app.url') === 'https://voucher-admin.alexandrarose.org.uk')
            <li>{{ Session::get('message') }}</li>
            <li>Service data endpoints</li>
            <li><a href="data/vouchers"><span class="glyphicon glyphicon-cog"></span>Vouchers</a></li>
            <li><a href="data/users"><span class="glyphicon glyphicon-cog"></span>Users</a></li>
            <li><a href="data/traders"><span class="glyphicon glyphicon-cog"></span>Traders</a></li>
            <li><a href="data/markets"><span class="glyphicon glyphicon-cog"></span>Markets</a></li>
            <li class="danger"><a href="data/reset-data"><span class="glyphicon glyphicon-cog"></span>Reset data</a></li>
            @endUnless

        </ul>

    </div>

    <div id="right">
        This is the main content area in the admin dashboard.
    </div>

</div>

@endsection
