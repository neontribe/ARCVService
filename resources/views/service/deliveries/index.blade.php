@extends('service.layouts.app')

@section('content')
<div id="container">

    @include('service.includes.sidebar')

    <div id="main-content">
        <h1>Sent vouchers</h1>
        @if (Session::get('message'))
            <div class="alert alert-success">
                {{ Session::get('message') }}
            </div>
        @endif
        <table class="table table-striped sortable">
            <thead>
                <tr>
                    <th>Voucher Range<button onClick="orderAscending('range')"><span class="glyphicon glyphicon-chevron-down"></span></button></th>
                    <th>Centre<button onClick="orderAscending('centre')"><span class="glyphicon glyphicon-chevron-down"></span></button></th>
                    <th>Date Sent<button onClick="orderAscending('date')"><span class="glyphicon glyphicon-chevron-down"></span></button></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($deliveries as $delivery)
                    <tr class="{{ $loop->index}}">
                        <td class="range">{{ $delivery->range }}</td>
                        <td class="centre">{{ $delivery->centre }}</td>
                        <td class="date">{{ $delivery->date }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <script>
        function orderAscending(column) {
            console.log(column);
        };
    </script>
</div>

@endsection
