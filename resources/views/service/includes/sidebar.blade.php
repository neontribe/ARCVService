<div id="sidebar">

    <ul>
        <li>Admin Dashboard</li>
        <li><a href="{{ url('/') }}"><span class="glyphicon glyphicon-home"></span>Dashboard</a></li>
        <li><a href="{{ url('/vouchers/create') }}"><span class="glyphicon glyphicon-plus"></span>Add voucher codes</a></li>
        <li><a href="{{ url('/vouchers') }}"><span class="glyphicon glyphicon-th-list"></span>View live vouchers</a></li>
        <li><a href="{{ url('/workers') }}"><span class="glyphicon glyphicon-th-list"></span>View workers</a></li>
        <li><a href="{{ url('/centres') }}"><span class="glyphicon glyphicon-th-list"></span>View Children's Centres</a></li>

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

    @if (session('notification'))
        <div class="alert alert-success">
            {{ session('notification') }}
        </div>
    @endif

</div>
