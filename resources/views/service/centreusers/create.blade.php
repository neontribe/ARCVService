@extends('service.layouts.app')
@section('content')

<div id="container">
    @include('service.includes.sidebar')
    <div id="main-content">

        <h1>Add a Children's Centre Worker</h1>

        <p>Use the form below to add a new worker. Add their name, email address, home centre and any alternative neighbouring centres they may work from.</p>

        <form role="form" class="styled-form" method="POST" action="{{ route('admin.centreusers.store') }}">
            {!! csrf_field() !!}
            <div class="horizontal-container">
                <div>
                    <label for="name" class="required">Name</label>
                    <input type="text" id="name" name="name" class="{{ $errors->has('name') ? 'error' : '' }}" required>
                </div>
                <div>
                    <label for="email" class="required">Email Address</label>
                    <input type="email" id="email" name="email" class="{{ $errors->has('email') ? 'error' : '' }}" required>
                </div>
                <div class="select">
                    <label for="worker_centre">Home Centre</label>
                    <select name="worker_centre" id="worker_centre" class="{{ $errors->has('worker_centre') ? 'error' : '' }}" required>
                        <option value="">Choose one</option>
                        @foreach ($centres as $centre)
                            <option value="{{ $centre->id }}">{{ $centre->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="alternatives" class="hidden">
                    <p><strong>Set Neighbours as Alternatives</strong></p>
                    <div id="centres" class="checkboxes">
                    </div>
                </div>
            </div>
            <button type="submit" id="createWorker">Add worker</button>
        </form>

        <script>
            function buildCheckboxes(data) {
                if (data.length > 0) {
                    var boxes = $.map(data, function(obj) {
                        return  '<div class="checkbox-group">' +
                            '<input type="checkbox" id="neighbor-' +obj.id+ '" name="alternative_centres[]" value="' +obj.id+ '" >' +
                            '<label for="neighbor-' +obj.id+ '">' +obj.name+ '</label>' +
                            '</div>';
                    });
                    return boxes.join('');
                }
                return '<div><p>This centre has no neighbors.</p></div>';
            }

            $(document).ready(
                function () {
                    $('#worker_centre').change(function () {
                        $('#alternatives').removeClass('hidden');
                        var centreId = parseInt($('#worker_centre').val());
                        // It's probably a number
                        if (!isNaN(centreId)) {
                            $.get('/centres/' + centreId + '/neighbors')
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