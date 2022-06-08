<div>
    <span>Add children or a pregnancy:</span>
</div>

<div id="addChildDobInput" class="dob-input-container">
    @include('store.partials.dobInput')
    <button id="add-dob" class="link-button link-button-large">
        <i class="fa fa-plus button-icon" aria-hidden="true"></i>
        Add Child or Pregnancy
    </button>
</div>

<div>
    <p><span id="dob-error" class="invalid-error"></span></p>
</div>

<script>
    // setup the dobInput
    $("#addChildDobInput").dobInput();

    $("#add-dob").click(function (e) {
        e.preventDefault();
        // broadcast that we've validated and made the date object
        $("#addChildDobInput").trigger('childInput:submitted');
    });

    // Error message
    $(document).on('childInput:error', function(e, errorMsg) {
        console.log(errorMsg);
        $('#dob-error').text(errorMsg);
    });

    // Clear error message
    $('document').on('childInput:validated', function(e) {
        $('#dob-error').text('');
    })

    // Adding this here for now but commented out due to weird display issues
    /* function addDobRow(e, dateObj, verified) {
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
            moment().diff(valueDate, 'months') % 12 +
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

    $(document).on('childInput:validated', addDobRow);

    // In the case of failed submission, iterate the children previously submitted
    $(".js-old-child").each(function (index) {
        // Grab the data out of the data attributes
        var dob = $(this).data("dob");
        var verified = $(this).data("verified");

        // Convert to useful formats - add_child_form partial should have validated these
        var dateObj = moment(dob, "YYYY-MM", true).format("MMM YYYY");
        var childKey = Math.random();
        var displayVerified = verified === 1 ? "checked" : null;
        var displayMonths = moment().diff(dob, 'months') % 12;
        var displayYears = moment().diff(dob, 'years');

        // Create and append new style columns
        var ageColumn = '<td class="age-col">' + displayYears + ' yr, ' + displayMonths + ' mo</td>';
        var dobColumn = '<td class="dob-col"><input name="children[' + childKey + '][dob]" type="hidden" value="' + dob + '" >' + dateObj + '</td>';
        var idColumn = '<td class="verified-col relative"><input type="checkbox" class="styled-checkbox inline-dob" name="children[' + childKey + '][verified]" id="child' + childKey + '" ' + displayVerified + ' value="' + verified + '"><label for="child' + childKey + '"><span class="visually-hidden">Toggle ID checked</span></label>' + '</td>';
        var removeColumn = '<td class="remove-col"><button type="button" class="remove_date_field"><i class="fa fa-minus" aria-hidden="true"></i></button></td>';

        $(this).append(ageColumn);
        $(this).append(dobColumn);
        $(this).append(idColumn);
        $(this).append(removeColumn);
    });
    */
</script>
