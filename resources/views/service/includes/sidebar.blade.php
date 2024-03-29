<div id="sidebar">

    <ul>
        <li>Admin Dashboard</li>
        <li><a href="{{ url('/') }}"><span class="glyphicon glyphicon-home"></span>Dashboard</a></li>
        <li><a href="{{ url('/vouchers/create') }}"><span class="glyphicon glyphicon-plus"></span>Add voucher codes</a></li>
        <li><a href="{{ url('/vouchers') }}"><span class="glyphicon glyphicon-th-list"></span>View live vouchers</a></li>
        <li><a href="{{ url('/deliveries') }}"><span class="glyphicon glyphicon-th-list"></span>View sent vouchers</a></li>
        <li><a href="{{ url('/deliveries/create') }}"><span class="glyphicon glyphicon-send"></span>Send vouchers</a></li>
        <li><a href="{{ url('/vouchers/void') }}"><span class="glyphicon glyphicon-fire"></span>Void voucher codes</a></li>
        <li><a href="{{ url('/payments') }}"
               @if($hasPayments !==false) class ="payments" @endif><span class="glyphicon glyphicon-gbp"></span>Payment Requests</a></li>
        {{--Adds colour change if outstanding payment requests--}}
        <li><a href="{{ url('/workers') }}"><span class="glyphicon glyphicon-th-list"></span>View workers</a></li>
        <li><a href="{{ url('/workers/create') }}"><span class="glyphicon glyphicon-plus"></span>Add workers</a></li>
        <li><a href="{{ url('/centres') }}"><span class="glyphicon glyphicon-th-list"></span>View children's centres</a></li>
        <li><a href="{{ url('/centres/create') }}"><span class="glyphicon glyphicon-plus"></span>Add children's centres</a></li>
        <li><a href="{{ url('/sponsors') }}"><span class="glyphicon glyphicon-th-list"></span>View areas</a></li>
        <li><a href="{{ url('/sponsors/create') }}"><span class="glyphicon glyphicon-plus"></span>Add areas</a></li>
        <li><a href="{{ url('/markets') }}"><span class="glyphicon glyphicon-th-list"></span>View markets</a></li>
        <li><a href="{{ url('/markets/create') }}"><span class="glyphicon glyphicon-plus"></span>Add markets</a></li>
        <li><a href="{{ url('/traders') }}"><span class="glyphicon glyphicon-th-list"></span>View traders</a></li>
        <li><a href="{{ url('/traders/create') }}"><span class="glyphicon glyphicon-plus"></span>Add traders</a></li>
        @unless(Config('app.url') === 'https://voucher-admin.alexandrarose.org.uk')
        <li>{{ Session::get('message') }}</li>
        <li>Service data endpoints</li>
        <li><a href="{{ route('data.vouchers.index') }}"><span class="glyphicon glyphicon-cog"></span>Vouchers</a></li>
        <li><a href="{{ route('data.users.index') }}"><span class="glyphicon glyphicon-cog"></span>Users</a></li>
        <li><a href="{{ route('data.traders.index') }}"><span class="glyphicon glyphicon-cog"></span>Traders</a></li>
        <li><a href="{{ route('data.markets.index') }}"><span class="glyphicon glyphicon-cog"></span>Markets</a></li>
        <li class="danger"><a href="{{ route('data.reset') }}"><span class="glyphicon glyphicon-cog"></span>Reset data</a></li>
        @endUnless
    </ul>

</div>
