@section('hoist-head')
    <script src="{{ asset('store/js/moment-2.20.1.min.js')}}"></script>
@endsection

<div class="col fit-height">
    <div>
        <img src="{{ asset('store/assets/pregnancy-light.svg') }}" alt="logo">
        <h2>Household</h2>
    </div>
    <div>
        <p>To add a household member, complete the box below with their age.</p>
    </div>
    @include('store.partials.add_participant_form', ['verifying' => $verifying])
    <div class="added">
        <label for="child_wrapper">You have added:</label>
        <table>
            <thead>
            <tr>
                <td class="age-col">Age</td>
                <td class="dob-col"></td>
                <td class="remove-col"></td>
            </tr>
            </thead>
            <tbody id="child_wrapper">
            @if(is_array(old('children')) || (!empty(old('children'))))
                @foreach (old('children') as $old_child )
                    <tr class="js-old-child"
                        data-dob={{ $old_child['dob'] }}
                    ></tr>
                @endforeach
            @endif
            </tbody>
        </table>
    </div>
</div>

<script>
    function addAgeRow(e, dateObj, verified) {
        // setup fields
        // It's a valid date, so manufacture a human-readable string
        var valueDate = dateObj.format("YYYY-MM");

        // give the kids a random key
        var childKey = Math.random();

        // Create the table columns
        var ageColumn = (moment().diff(valueDate, 'days') > 0)
            ? '<td class="age-col">' +
            moment().diff(valueDate, 'years') + ' yr, ' +
            moment().diff(valueDate, 'months') % 12 +
            ' mo</td>'
            : '<td class="age-col">P</td>'
        ;

        var dobColumn = '<td class="dob-col"><input name="children[' + childKey + '][dob]" type="hidden" value="' + valueDate + '" ></td>';
        var removeColumn = '<td class="remove-col"><button type="button" class="remove_date_field"><i class="fa fa-minus" aria-hidden="true"></i></button></td>';

        // add an input
        $("#child_wrapper").append('<tr>' + ageColumn + dobColumn + removeColumn + '</tr>');

        // emit event
        $(document).trigger('childRow:updated');
    }
    $(document).on('childInput:validated', addAgeRow);
</script>
