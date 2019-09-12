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
                    <th>Voucher Range
                        <span>@include('service.partials.sortableChevron', ['route' => 'admin.deliveries.index', 'orderBy' => 'range', 'direction' => request('direction') ])</span>
                    </th>
                    <th>Centre
                        <span>@include('service.partials.sortableChevron', ['route' => 'admin.deliveries.index', 'orderBy' => 'centre', 'direction' => request('direction') ])</span>
                    </th>
                    <th>Date Sent
                        <span>@include('service.partials.sortableChevron', ['route' => 'admin.deliveries.index', 'orderBy' => 'dispatchDate', 'direction' => request('direction') ])</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($deliveries as $delivery)
                    <tr class="{{ $loop->index}}">
                        <td class="range">{{ $delivery->range }}</td>
                        <td class="centre">{{ $delivery->centre->name }}</td>
                        <td class="date">{{ $delivery->dispatched_at->format('d-m-Y')}}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
