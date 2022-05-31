<div>
    <span>Add children or a pregnancy:</span>
</div>
<div class="dob-input-container">
    <div class="dob-input">
        <label for="dob-month" class="block">Month</label>
        <input id="dob-month" name="dob-month" type="number" pattern="[0-9]*" min="0" max="12">
    </div>
    <div class="dob-input">
        <label for="dob-year" class="block">Year</label>
        <input id="dob-year" name="dob-year" type="number" pattern="[0-9]*" min="0"
                max="{{ Carbon\Carbon::now()->year }}">
    </div>
    @if ( $verifying )
    <div class="dob-input relative">
        <input type="checkbox" class="styled-checkbox" id="dob-verified" name="dob-verified">
        <label for="dob-verified">ID Checked</label>
    </div>
    @endif
</div>
<button id="add-dob" class="link-button link-button-large">
    <i class="fa fa-plus button-icon" aria-hidden="true"></i>
    Add Child or Pregnancy
</button>
<div>
<p><span id="dob-error" class="invalid-error"></span></p>
</div>

@section('hoist-head')
    <script src="{{ asset('store/js/moment-2.20.1.min.js')}}"></script>
@endsection

<script>

    function resetDobInput() {
        // reset form
        $('#dob-error').text('');
        $('#dob-year').val('');
        $('#dob-month').val('');
        $('#dob-verified').prop('checked', false);
        $('#dob-month').focus();
    }
    $(document).on('childRow:updated', resetDobInput);

    function addDobRow(e, dateObj, verified) {
        // setup fields
        // It's a valid date, so manufacture a human readable string
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
    $(document).on('childInput:submitted', addDobRow);

    $("#add-dob").click(function (e) {
        e.preventDefault();

        // error field for messages
        var dobError = $('#dob-error');

        // get the dates
        var month = $('#dob-month').val();
        var year = $('#dob-year').val();

        // If input fields are too small, return
        if (month.length < 1 || year.length <= 2) {
            dobError.text('Invalid Date');
            return false;
        }

        var dateObj = moment(year + '-' + month, "YYYY-M", true).startOf('month');

        if (!dateObj.isValid()) {
            switch (dateObj.invalidAt()) {
                case (1) : // month
                    dobError.text('Invalid Month');
                    break;
                case (0) : // year
                    dobError.text('Invalid Year');
                    break;
                default :
                    dobError.text('Invalid Date');
            }
            return false;
        }

        if (dateObj.isAfter(
            moment().startOf('month').add(9, 'month')
        )) {
            dobError.text('Invalid Date: over 9 months away.');
            return false;
        }
        // broadcast that we've validated and made the
        $(document).trigger('childInput:submitted', [
            dateObj, $('#dob-verified').is(":checked")
        ]);
    });
</script>