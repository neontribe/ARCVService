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
                    <tr>
                        <td class="range {{ $loop->index}}">{{ $delivery->range }}</td>
                        <td class="centre {{ $loop->index}}">{{ $delivery->centre }}</td>
                        <td class="date {{ $loop->index}}">{{ $delivery->date }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <script>

            function orderAscending(column) {
                // Create array of each item in column
                var Arr = [];
                const colClass = '.' + column;

                $(colClass).each(function(index, element){ 
                    Arr.push(element.innerHTML);
                });

                // Order array alphabetically
                var sortedArr = Arr.sort();

                // Replace existing table elements with newly ordered ones
                sortedArr.forEach(function(item, index) {

                    // Replace existing elements using index and add new index to sibling
                    const existingEl = colClass + '.' + index;
                    const newEl = '<td class="' + column + ' ' + index + '">' + item + '</td>';

                    // Traverse siblings

                    // target current siblings
                    elSiblings = $(existingEl).siblings();
                    $(elSiblings).replaceWith
                    $(existingEl).replaceWith(newEl);
                });
            };

    </script>
</div>

@endsection
