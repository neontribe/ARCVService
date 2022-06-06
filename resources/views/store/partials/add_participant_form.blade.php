<div>
    <span>Add Household Member:</span>
</div>
<div class="age-input-container">
    @include('store.partials.ageInput')
</div>
<button id="add-age" class="link-button link-button-large">
    <i class="fa fa-plus button-icon" aria-hidden="true"></i>
    Add Household Member
</button>
<div>
<p><span id="age-error" class="invalid-error"></span></p>
</div>

@section('hoist-head')
    <script src="{{ asset('store/js/moment-2.20.1.min.js')}}"></script>
@endsection

<script>
    $("#addChildDobInput").ageInput();

    function addAgeRow(e, dateObj, verified) {
        // setup fields
        // It's a valid date, so manufacture a human-readable string
        var innerTextDate = dateObj.format("MMM YYYY");
        var valueDate = dateObj.format("YYYY-MM");

        // Organise the ID verification values and display
        var verifiedValue = verified ? 1 : 0;
        var displayVerified = verified ? "checked" : null;

        // give the kids a random key
        var childKey = Math.random();

        // Create the table columns
        var ageColumn = (moment().diff(valueDate, 'days') > 0)
            ? '<td class="age-col">' +
            moment().diff(valueDate, 'years') + ' yr, ' +
            moment().diff(valueDate, 'months') % 12  +
            ' mo</td>'
            : '<td class="age-col">P</td>'
        ;

        var dobColumn = '<td class="dob-col"><input name="children[' + childKey + '][dob]" type="hidden" value="' + valueDate + '" >' + innerTextDate + '</td>';
        var idColumn = (verified)
            ? '<td class="verified-col relative"><input type="checkbox" class="styled-checkbox inline-dob" name="children[' + childKey + '][verified]" id="child' + childKey + '" ' + displayVerified + ' value="' + verifiedValue + '"><label for="child' + childKey + '"><span class="visually-hidden">Toggle ID checked</span></label>' + '</td>'
            : '<td class="verified-col relative"></td>';
        var removeColumn = '<td class="remove-col"><button type="button" class="remove_date_field"><i class="fa fa-minus" aria-hidden="true"></i></button></td>';

        // add an input
        $("#child_wrapper").append('<tr>' + ageColumn + dobColumn + idColumn + removeColumn + '</tr>');

        // emit event
        $(document).trigger('childRow:updated');
    }
    $(document).on('childInput:submitted', addAgeRow);

    $("#add-age").click(function (e) {
        e.preventDefault();

        // error field for messages
        var dobError = $('#age-error');

        // get the dates
        var age = $('#age').val();
        var dateObj = moment().subtract(age, 'years').startOf('month');
        if (!dateObj.isValid()) {
            dobError.text('Invalid Age');
            return false;
        }

        // broadcast that we've validated and include the date
        $(document).trigger('childInput:submitted', [ dateObj, false ]);
    });
</script>