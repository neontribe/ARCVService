@extends('service.layouts.app')

@section('content')
    <div id="container">

        @include('service.includes.sidebar')

        <div id="main-content">
            <h1>Traders</h1>
            @if (Session::get('message'))
                <div class="alert alert-success">
                    {{ Session::get('message') }}
                </div>
            @endif
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Market</th>
                    <th>Area</th>
                    <th>Users</th>
                    <th>Payments</th>
                    <th>Info</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($traders as $trader)
                    <tr class="{{ isset($trader->disabled_at) ? 'inactive' : 'active' }}">
                        <td>{{ $trader->name }}</td>
                        <td>{{ $trader->market->name }}</td>
                        <td>{{ $trader->market->sponsor->name }}</td>
                        <td>
                            @if ($trader->users->count() > 0)
                                <ul>
                                    @foreach ($trader->users->sortBy('name') as $user)
                                        <li>{{ $user->name }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.trader-payment-history.show', ['trader' => $trader ]) }}" class="link">
                                <div class="link-button link-button-small">
                                    <i class="fa fa-pencil button-icon" aria-hidden="true"></i>View
                                </div>
                            </a>
                        </td>
                        <td>
                            <a href="{{ route('admin.traders.edit', ['id' => $trader->id ]) }}" class="link">
                                <div class="link-button link-button-small">
                                    <i class="fa fa-pencil button-icon" aria-hidden="true"></i>Edit
                                </div>
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <a href="{{ route('admin.traders.download') }}" class="link" target="_blank" rel="noopener noreferrer">
            <div class="link-button download-list">
                Download Trader List
            </div>
        </a>
        </div>

    </div>

@endsection