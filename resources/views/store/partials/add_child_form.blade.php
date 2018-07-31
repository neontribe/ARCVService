<div class="add-child-form">
    <p><span id="dob-error"></span></p>
	<div class="form-group">
		<label for="dob-month" >Month</label>
		<input id="dob-month" name="dob-month" pattern="[0-9]*" min="0" max="12" type="number">
	</div>
	<div class="form-group">
		<label for="dob-year">Year</label>
		<input id="dob-year" name="dob-year" type="number" pattern="[0-9]*" min="0" max="{{ Carbon\Carbon::now()->year }}">
	</div>
	<button id="add-dob" class="add-dob">
        <i class="fa fa-plus" aria-hidden="true"></i>
    </button>
</div>

@section('hoist-head')
	<script src="{{ asset('js/moment-2.20.1.min.js')}}"></script>
@endsection

<script>
$(document).ready(
    function() {
        var el = $("#child_wrapper");
        var addDateButton = $("#add-dob");
        var dobError = $('#dob-error');

        $(addDateButton).click(function (e) {
            e.preventDefault();

            var monthEl = $('#dob-month');
            var yearEl = $('#dob-year');

            // If input fields are too small, return
            if (monthEl.val().length < 1 || yearEl.val().length <= 2) {
                dobError.text('Invalid Date');
                return false;
            }

            // get the dates
            var month = monthEl.val();
            var year = yearEl.val();
            var dateObj = moment(year + '-' + month, "YYYY-MM", true).startOf('month');

            if (!dateObj.isValid()) {
                switch(dateObj.invalidAt()) {
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
            var lowerBoundDate = moment().startOf('month').subtract(5, 'year');
            var upperBoundDate = moment().startOf('month').add(9, 'month');

            if (dateObj.isBefore(lowerBoundDate))
            {
                dobError.text('Invalid Date: over 5 year ago.');
                return false;
            }

            if (dateObj.isAfter(upperBoundDate))
            {
                dobError.text('Invalid Date: over 9 months away.');
                return false;
            }

			// It's a valid date, so manufacture a human readable string
            var innerTextDate = dateObj.format("MMM YYYY");
            var valueDate = dateObj.format("YYYY-MM");

            // add an input
            $(el).append('<tr><td><input name="children[]" type="hidden" value="' +valueDate+ '" >' + innerTextDate + '</td><td><button type="button" class="remove_date_field"><i class="fa fa-minus" aria-hidden="true"></i></button></td></tr>');

			// reset form
            dobError.text('');
            yearEl.val('');
            monthEl.val('');
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