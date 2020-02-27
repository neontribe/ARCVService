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
    $(document).ready(
        function () {
            var el = $("#child_wrapper");
            var addDateButton = $("#add-dob");
            var dobError = $('#dob-error');

            $(addDateButton).click(function (e) {
                e.preventDefault();

                var monthEl = $('#dob-month');
                var yearEl = $('#dob-year');
                var idCheckedEl = $('#dob-verified');

                // If input fields are too small, return
                if (monthEl.val().length < 1 || yearEl.val().length <= 2) {
                    dobError.text('Invalid Date');
                    return false;
                }

                // get the dates
                var month = monthEl.val();
                var year = yearEl.val();
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
                    }
                    return false;
                }

                // Check date is less than 5 years ago of 10 months away.
                // var lowerBoundDate = moment().startOf('month').subtract(5, 'year');
                var upperBoundDate = moment().startOf('month').add(9, 'month');

                /*
                if (dateObj.isBefore(lowerBoundDate)) {
                    dobError.text('Invalid Date: over 5 year ago.');
                    return false;
                }
                */

                if (dateObj.isAfter(upperBoundDate)) {
                    dobError.text('Invalid Date: over 9 months away.');
                    return false;
                }

                // It's a valid date, so manufacture a human readable string
                var innerTextDate = dateObj.format("MMM YYYY");
                var valueDate = dateObj.format("YYYY-MM");

                // Make some age display values based on if it's the future.
                var ageString = (moment().diff(valueDate, 'days') > 0)
                    ? '<td class="age-col">' +
                        moment().diff(valueDate, 'years') + ' yr, ' +
                        moment().diff(valueDate, 'months') % 12  +
                        ' mo</td>'
                    : '<td class="age-col">P</td>'
                    ;

                // Organise the ID verification values and display
                var verifiedValue = idCheckedEl.is(":checked") ? 1 : 0;
                var displayVerified = idCheckedEl.is(":checked") ? "checked" : null;
                var childKey = Math.random();

                // Create the table columns
                var ageColumn = ageString;
                var dobColumn = '<td class="dob-col"><input name="children[' + childKey + '][dob]" type="hidden" value="' + valueDate + '" >' + innerTextDate + '</td>';
                var idColumn = (idCheckedEl.length)
                    ? '<td class="verified-col relative"><input type="checkbox" class="styled-checkbox inline-dob" name="children[' + childKey + '][verified]" id="child' + childKey + '" ' + displayVerified + ' value="' + verifiedValue + '"><label for="child' + childKey + '"><span class="visually-hidden">Toggle ID checked</span></label>' + '</td>'
                    : '<td class="verified-col relative"></td>';
                var removeColumn = '<td class="remove-col"><button type="button" class="remove_date_field"><i class="fa fa-minus" aria-hidden="true"></i></button></td>';

                // add an input
                $(el).append('<tr>' + ageColumn + dobColumn + idColumn + removeColumn + '</tr>');

                // reset form
                dobError.text('');
                yearEl.val('');
                monthEl.val('');
                idCheckedEl.prop('checked', false);
                monthEl.focus();

            });

            $(el).on("click", ".remove_date_field", function (e) {
                e.preventDefault();
                $(this).closest('tr').remove();
                return false;
            });
        }
    );
</script>