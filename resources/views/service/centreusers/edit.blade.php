@extends('service.layouts.app')
@section('content')

<div id="container">
    @include('service.includes.sidebar')
    <div id="main-content">

        <h1>Edit a Children's Centre Worker</h1>

        <p>Use the form below to amend a Children's Centre Worker's details.</p>

        <!-- ADD NEW POST ROUTE HERE eg. method="POST" action="{{ route('admin.vouchers.storebatch') }}" -->
        <form role="form" class="styled-form">
            {!! csrf_field() !!}
            <div class="horizontal-container">
                <div>
                    <label for="name" class="required">Name</label>
                    <input type="text" id="name" value="{{ $worker->name }}" name="name" class="{{ $errors->has('name') ? 'error' : '' }}" required>
                </div>
                <div>
                    <label for="email" class="required">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ $worker->email }}" class="{{ $errors->has('email') ? 'error' : '' }}" required>
                </div>
                <div class="select">
                    <label for="worker_centre">Home Centre</label>
                    <select name="worker_centre" id="worker_centre" class="{{ $errors->has('worker_centre') ? 'error' : '' }}" required>
                        @foreach($centresBySponsor as $sponsor)
                        <optgroup label="{{ $sponsor->name }}">
                            @foreach ($sponsor->centres as $centre)
                                <option value="{{ $centre->id }}"
                                        @if($centre->selected === "home")
                                        SELECTED
                                        @endif
                                        @if($centre->selected !== false)
                                            data-workercentre="{{ $centre->selected }}"
                                        @endif
                                >
                                    {{ $centre->name }}
                                </option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="checkboxes">
                    <label for="alternative_centres">Set Neighbours as Alternatives</label>
                    <div id="alternatives">
                        <p><strong>Set Neighbours as Alternatives</strong></p>
                        <div id="centres" class="checkboxes">
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" id="createWorker">Add worker</button>
        </form>
        <script>
            function buildCheckboxes(data) {
                if (data.length > 0) {
                    var boxes = $.map(data, function(obj) {
                        var div = $('<div class="checkbox-group">');

                        // Make an input
                        var input = $('<input>')
                            .attr({
                            type    : 'checkbox',
                            name    : 'alternate_centres[]',
                            value   : obj.id,
                            id      : 'neighbour-' +obj.id,
                            checked : !!(obj.selected)
                        }).appendTo(div);

                        input.click(function() {
                            ;
                        });

                        // Make a label
                        $('<label>').attr({
                           for  : 'neighbour-' +obj.id
                        }).text(obj.name).appendTo(div);

                        // Return the div as a string
                        return div;
                    });
                    return boxes;
                }
                return $('<div><p>This centre has no neighbours.</p></div>');
            }

            $(document).ready(
                // show the boxes
                function () {
                    $('#worker_centre').change(function () {
                        $('#alternatives').removeClass('hidden');
                        var centreSelect = $('#worker_centre');
                        var prevCentre = $("option[data-workercentre='home']");

                        // If new one is an alternate, set alternate on old one
                        if (centreSelect.find(':selected').attr('data-workercentre')) {
                            prevCentre.attr('data-workercentre', 'alternate');
                        } else {
                            // else remove attribute (unselected)
                            prevCentre.removeAttr('data-workercentre');
                        }

                        // Setup data
                        var data = $("#worker_centre")
                            .find(':selected')
                            .siblings()
                            .map(function(index,centre) {
                                return {
                                    id : centre.value,
                                    name : centre.text,
                                    selected : $(centre).attr('data-workercentre')
                                }
                            });

                        // Get the centreID
                        var centreId = parseInt(centreSelect.val());

                        // It's probably a number
                        if (!isNaN(centreId)) {
                            $('#centres').children().remove();
                            $.each(buildCheckboxes(data), function(i, box){
                                box.appendTo('#centres');
                            });
                        }
                    });
                }
            );
        </script>
    </div>
</div>

@endsection