<div id="sidebar">

    {{-- ===================== Dashboard ===================== --}}
    <ul class="sidebar-section">
        <li class="sidebar-item">
            <a href="{{ url('/') }}">
                <span class="glyphicon glyphicon-home"></span>
                Dashboard
            </a>
        </li>
    </ul>

    {{-- ===================== Voucher Management ===================== --}}
    <ul class="sidebar-section">
        <li class="sidebar-heading collapsed" data-toggle="collapse" data-target="#vouchers-menu" aria-expanded="false">
            <span class="glyphicon glyphicon-tags"></span>
            <span class="sidebar-heading-label">Vouchers</span>
            <span class="caret"></span>
        </li>
        <div id="vouchers-menu" class="collapse" aria-expanded="false">
            <li class="sidebar-item">
                <a href="{{ url('/vouchers/create') }}">
                    <span class="glyphicon glyphicon-plus"></span>
                    Add voucher codes
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ url('/vouchers') }}">
                    <span class="glyphicon glyphicon-th-list"></span>
                    View live vouchers
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ url('/deliveries') }}">
                    <span class="glyphicon glyphicon-th-list"></span>
                    View sent vouchers
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ url('/deliveries/create') }}">
                    <span class="glyphicon glyphicon-send"></span>
                    Send vouchers
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ url('/vouchers/void') }}">
                    <span class="glyphicon glyphicon-fire"></span>
                    Void voucher codes
                </a>
            </li>
        </div>
    </ul>

    {{-- ===================== Payments ===================== --}}
    <ul class="sidebar-section">
        <li class="sidebar-heading collapsed" data-toggle="collapse" data-target="#payments-menu" aria-expanded="false">
            <span class="glyphicon glyphicon-gbp"></span>
            <span class="sidebar-heading-label">Payments</span>
            @if($hasPayments !== false)
                <span class="badge pull-right">!</span>
            @endif

            <span class="caret"></span>
        </li>
        <div id="payments-menu" class="collapse" aria-expanded="false">
            <li class="sidebar-item">
                <a href="{{ url('/payments') }}" @if($hasPayments !== false) class="payments" @endif>
                    <span class="glyphicon glyphicon-th-list"></span>
                    Payment Requests
                </a>
            </li>
        </div>
    </ul>

    {{-- ===================== People & Centres ===================== --}}
    <ul class="sidebar-section">
        <li class="sidebar-heading collapsed" data-toggle="collapse" data-target="#people-menu" aria-expanded="false">
            <span class="glyphicon glyphicon-user"></span>
            <span class="sidebar-heading-label">People &amp; Centres</span>
            <span class="caret"></span>
        </li>
        <div id="people-menu" class="collapse" aria-expanded="false">
            <li class="sidebar-item">
                <a href="{{ url('/workers') }}">
                    <span class="glyphicon glyphicon-th-list"></span>
                    View workers
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ url('/workers/create') }}">
                    <span class="glyphicon glyphicon-plus"></span>
                    Add workers
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ url('/centres') }}">
                    <span class="glyphicon glyphicon-th-list"></span>
                    View children's centres
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ url('/centres/create') }}">
                    <span class="glyphicon glyphicon-plus"></span>
                    Add children's centres
                </a>
            </li>
        </div>
    </ul>

    {{-- ===================== Locations & Traders ===================== --}}
    <ul class="sidebar-section">
        <li class="sidebar-heading collapsed" data-toggle="collapse" data-target="#locations-menu" aria-expanded="false">
            <span class="glyphicon glyphicon-map-marker"></span>
            <span class="sidebar-heading-label">Locations &amp; Traders</span>
            <span class="caret"></span>
        </li>
        <div id="locations-menu" class="collapse" aria-expanded="false">
            <li class="sidebar-item">
                <a href="{{ url('/sponsors') }}">
                    <span class="glyphicon glyphicon-th-list"></span>
                    View areas
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ url('/sponsors/create') }}">
                    <span class="glyphicon glyphicon-plus"></span>
                    Add areas
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ url('/markets') }}">
                    <span class="glyphicon glyphicon-th-list"></span>
                    View markets
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ url('/markets/create') }}">
                    <span class="glyphicon glyphicon-plus"></span>
                    Add markets
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ url('/traders') }}">
                    <span class="glyphicon glyphicon-th-list"></span>
                    View traders
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ url('/traders/create') }}">
                    <span class="glyphicon glyphicon-plus"></span>
                    Add traders
                </a>
            </li>
        </div>
    </ul>

    {{-- ===================== Developer Tools (non-production only) ===================== --}}
    @unless(Config('app.url') === 'https://voucher-admin.alexandrarose.org.uk')
        <ul class="sidebar-section sidebar-section--dev">
            <li class="sidebar-heading collapsed" data-toggle="collapse" data-target="#dev-menu" aria-expanded="false">
                <span class="glyphicon glyphicon-cog"></span>
                <span class="sidebar-heading-label">Developer Tools</span>
                <span class="caret"></span>
            </li>
            <div id="dev-menu" class="collapse" aria-expanded="false">
                @if(Session::get('message'))
                    <li class="sidebar-message">{{ Session::get('message') }}</li>
                @endif
                <li class="sidebar-subheading">Service data endpoints</li>
                <li class="sidebar-item">
                    <a href="{{ route('data.vouchers.index') }}">
                        <span class="glyphicon glyphicon-cog"></span>
                        Vouchers
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('data.users.index') }}">
                        <span class="glyphicon glyphicon-cog"></span>
                        Users
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('data.traders.index') }}">
                        <span class="glyphicon glyphicon-cog"></span>
                        Traders
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="{{ route('data.markets.index') }}">
                        <span class="glyphicon glyphicon-cog"></span>
                        Markets
                    </a>
                </li>
                <li class="sidebar-item sidebar-item--danger">
                    <a href="{{ route('data.reset') }}">
                        <span class="glyphicon glyphicon-warning-sign"></span>
                        Reset data
                    </a>
                </li>
            </div>
        </ul>
    @endUnless

</div>
