@extends('service.layouts.app')

@section('content')
<div>
This is the admin dashboard.
</div>
@unless(Config('app.url') === 'https://voucher-admin.alexandrarose.org.uk')
    <p>{{ Session::get('message') }}</p>
    <h1>Service data endpoints</h1>
    <div>
        <ul>
            <li><a href="data/vouchers">Vouchers</a></li>
            <li><a href="data/users">Users</a></li>
            <li><a href="data/traders">Traders</a></li>
            <li><a href="data/markets">Markets</a></li>
            <li class="danger"><a href="data/reset-data">Reset data</a></li>
        </ul>
    </div>
@endUnless
@endsection
