@extends('service.layouts.app')
@section('content')

<div id="container">
    @include('service.includes.sidebar')
    <div id="main-content">

        <h1>Edit a Children's Centre Worker</h1>

        <p>Use the form below to amend a Children's Centre Worker's details.</p>

        <form role="form" class="styled-form" method="POST" action="{{ route('admin.centreusers.update', ['id' => $worker->id]) }}">
            {!! method_field('PUT') !!}
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
                    <div id="alternatives">
                        <p><strong>Set Neighbours as Alternatives</strong></p>
                        <div id="centres" class="checkboxes"></div>
                    </div>
                </div>
            </div>
            <button type="submit" id="updateWorker">Update worker</button>
        </form>
        <script>
            function buildCheckboxes() {
                // Setup data for checkboxes
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

                // Set the default
                var boxes =[$('<div><p>This centre has no neighbours.</p></div>')];

                // Iterate data to see if we need to change that
                if (data.length > 0) {
                    boxes = $.map(data, function(obj) {
                        var div = $('<div class="checkbox-group">');

                        // Make an input
                        $('<input>')
                            .attr({
                                type    : 'checkbox',
                                name    : 'alternative_centres[]',
                                value   : obj.id,
                                id      : 'neighbour-' +obj.id
                            })
                            .prop('checked', !!(obj.selected))
                            .change(updateModel)
                            .appendTo(div);

                        // Make a label for it
                        $('<label>').attr({
                                for  : 'neighbour-' +obj.id
                            })
                            .text(obj.name)
                            .appendTo(div);

                        // Return the div
                        return div;
                    });
                }
                // build checkboxes
                $('#centres').children().remove();
                $.each(boxes, function(i, box){
                    box.appendTo('#centres');
                });
            }

            // This updates our "model", select-option elements decorates with data-* items.
            function updateModel(e) {
                // Find the centre equiv to the checkbox what we changed.
                var centre = $('#worker_centre').find('option[value="' + e.target.value + '"]');
                $(e.target).prop('checked')
                    //if it's checked, set to 'alternate'
                    ? centre.attr('data-workercentre', 'alternate')
                    //if it's not checked, remove attribute
                    : centre.removeAttr('data-workercentre')
                ;
                // rebuild the checkboxes.
                buildCheckboxes();
            }

            // Start the page
            $(document).ready(
                function () {
                //setup a change method
                    $('#worker_centre').change(function () {
                        var newCentre = $('#worker_centre').find(':selected');
                        var prevCentre = $('#worker_centre').find("option[data-workercentre='home']");
                        // If new one is an alternate, set alternate on old one
                        if (newCentre.attr('data-workercentre') === 'alternate') {
                            prevCentre.attr('data-workercentre', 'alternate');
                        } else {
                            // else remove attribute (deselect) from old
                            prevCentre.removeAttr('data-workercentre');
                        }
                        // Set the new one to be home
                        newCentre.attr('data-workercentre', 'home');
                        // rebuild checkboxes for new centre
                        buildCheckboxes();
                    });
                    // show the boxes when we load
                    buildCheckboxes();
                });
        </script>
    </div>
</div>

@endsection