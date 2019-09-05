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
                    <th>Voucher Range<button><span class="glyphicon glyphicon-chevron-down"></span></button></th>
                    <th>Centre<button><span class="glyphicon glyphicon-chevron-down"></span></button></th>
                    <th>Date Sent<button><span class="glyphicon glyphicon-chevron-down"></span></button></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($deliveries as $delivery)
                    <tr class="{{ $loop->index}}">
                        <td class="range">{{ $delivery->range }}</td>
                        <td class="centre">{{ $delivery->centre->name }}</td>
                        <td class="date">{{ $delivery->dispatched_at}}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
