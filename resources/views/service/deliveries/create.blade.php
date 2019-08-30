@extends('service.layouts.app')
@section('content')

<div id="container">
    @include('service.includes.sidebar')
    <div id="main-content">

        <h1>Send vouchers</h1>

        <p>Use the form below to mark a batch of vouchers as being sent. Add the centre they're being sent to, start and end voucher codes of the batch and the date they're being sent.</p>

        <form role="form" class="styled-form">
            {!! csrf_field() !!}
            <div class="horizontal-container">
                <div class="select">
                    <label for="centre" class="required">Centre</label>
                    <select name="centre" id="centre" class="{{ $errors->has('centre') ? 'error' : '' }}" required>
                        <option value="">Choose one</option>
                        @foreach ($sponsors as $sponsor)
                            <optgroup label="{{$sponsor->name}}">
                            @foreach ($sponsor->centres as $centre)
                                <option value="{{ $centre->id }}">{{ $centre->name }}</option>
                            @endforeach
                        @endforeach
                    </select>
                    @if($errors->has('name')) <label for="name" class="alert-danger">{{ implode("<br>", $errors->get('name')) }}</label> @endif
                </div>
                <div>
                    <label for="voucher-start" class="required">Start Voucher</label>
                    <input type="text" id="voucher-start" name="voucher-start" class="{{ $errors->has('voucher-start') ? 'error' : '' }} uppercase" required >
                    @if($errors->has('voucher-start')) <label for="voucher-start" class="alert-danger">{{ implode("<br>", $errors->get('voucher-start')) }}</label> @endif
                </div>
                <div>
                    <label for="voucher-end" class="required">End Voucher</label>
                    <input type="text" id="voucher-end" name="voucher-end" class="{{ $errors->has('voucher-end') ? 'error' : '' }} uppercase" required >
                    @if($errors->has('voucher-end')) <label for="voucher-end" class="alert-danger">{{ implode("<br>", $errors->get('voucher-end')) }}</label> @endif
                </div>
                <div>
                <label for="date-sent" class="required">Date Sent</label>
                    <input type="date" id="date-sent" name="date-sent" value={{ date("Y-m-d") }} class="{{ $errors->has('date-sent') ? 'error' : '' }}" required >
                    @if($errors->has('date-sent')) <label for="date-sent" class="alert-danger">{{ implode("<br>", $errors->get('date-sent')) }}</label> @endif
                </div>
            </div>
            <button type="submit" id="createWorker">Add worker</button>
        </form>

        <script>
            function buildCheckboxes(data) {
                if (data.length > 0) {
                    var boxes = $.map(data, function(obj) {
                        return  '<div class="checkbox-group">' +
                            '<input type="checkbox" id="neighbour-' +obj.id+ '" name="alternative_centres[]" value="' +obj.id+ '" >' +
                            '<label for="neighbour-' +obj.id+ '">' +obj.name+ '</label>' +
                            '</div>';
                    });
                    return boxes.join('');
                }
                return '<div><p>This centre has no neighbours.</p></div>';
            }

            $(document).ready(
                function () {
                    $('#worker_centre').change(function () {
                        $('#alternatives').removeClass('hidden');
                        var centreId = parseInt($('#worker_centre').val());
                        // It's probably a number
                        if (!isNaN(centreId)) {
                            $.getJSON('/centres/' + centreId + '/neighbours')
                                .then(
                                    function (result) {
                                        // success; show the data
                                        $('#centres')
                                            .html(buildCheckboxes(result));
                                    },
                                    function () {
                                        // failure; show an error message
                                        $('#centres')
                                            .html('<div><p>Sorry, there has been an error.</p></div>');
                                    }
                                );
                        }
                    });
                }
            );
        </script>
    </div>
</div>

@endsection