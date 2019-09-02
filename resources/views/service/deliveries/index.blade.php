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
                    <th>Voucher Range<button onClick="orderByRange()"><span class="glyphicon glyphicon-chevron-down"></span></button></th>
                    <th>Centre</th>
                    <th>Date Sent</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($deliveries as $delivery)
                    <tr>
                        <td class="range {{ $loop->index}}">{{ $delivery->range }}</td>
                        <td>{{ $delivery->centre }}</td>
                        <td>{{ $delivery->date }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <script>

            function orderByRange() {
                // Create array of each range
                var deliveriesArr = [];
                $('.range').each(function(index, element){ 
                    deliveriesArr.push(element.innerHTML);
                });

                // Order array alphabetically
                var sortedDeliveriesArr = deliveriesArr.sort();

                // Replace them with each id="range"
                sortedDeliveriesArr.forEach(function(item, index) {
                    const existingEl = '.range.' + index;
                    const newEl = '<td class="range ' + index + '">' + item + '</td>';
                    $(existingEl).replaceWith(newEl);
                });
            };

    </script>
</div>

@endsection
