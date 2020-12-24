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
                    <th>Traders</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($markets as $market)
                    <tr>
                        <td>{{ $market->name }}</td>
                        <td>{{ $market->sponsor->name }}</td>
                        <td>
                            <a href="{{ route('admin.traders.create', ["market" => $market->id]) }}" class="link">
                                <div style="width:5em;" class="link-button link-button-small">
                                    <i class="fa fa-pencil button-icon" aria-hidden="true"></i>Add
                                </div>
                            </a>
                            @if ($market->traders()->count() > 0)
                            <ul>
                            @foreach ($market->traders->sortBy('name') as $trader)

                                <li class="{{ isset($trader->disabled_at)
                                    ? 'inactive'
                                    : 'active' }}"
                                >{{ $trader->name }}</li>
                            @endforeach
                            </ul>
                            @endif
                        </td>
                        <td >
                            <a href="{{ route('admin.markets.edit', ['id' => $market->id ]) }}" class="link">
                                <div style="width:5em;" class="link-button link-button-small">
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
