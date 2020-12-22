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
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($traders as $trader)
                    <tr>
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
        </div>

    </div>

@endsection