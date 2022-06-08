$(document).ready(
    function () {
        var maxFields = 10;
        var el = $("#carer_wrapper");
        var carer_el = $('#carer_adder_input');
        var fields = 1;

        /// add button click
        $("#add_collector").click(function (e) {
            e.preventDefault();
            if (carer_el.val().length <= 1) {
                return false;
            }
            if (fields < maxFields) {
                fields++;
                $(el).append('<tr><td><input name="new_carers[]" type="text" value="' + carer_el.val() + '" ></td><td><button type="button" class="remove_field"><i class="fa fa-minus" aria-hidden="true"></i></button></td></tr>');
                carer_el.val('');
            }
        });

        $(el).on("click", ".remove_field", function (e) {
            e.preventDefault();
            $(this).closest('tr').remove();
            fields--;
        })
    }
);

// If enter is pressed, keyboard is hidden on iPad and form submit is disabled
$('#carer').on('keyup keypress', function (e) {
    if (e.which === 13) {
        e.preventDefault();
        document.activeElement.blur();
        $("input").blur();
        return false;
    }
});

//remove invalid class & error span when input is selected/tabbed to
$('#carer').on('click focus', function () {
    $(this).removeClass("invalid");
    $('#carer-span').addClass('collapsed');
});

$('.clickable-span').click(function (e) {
    $('#more-family-info').removeClass('collapsed');
    e.preventDefault();
});

$('.remove').click(function (e) {
    $('#expandable').removeClass('collapsed');
    $('#leaving').addClass('expanded');
    e.preventDefault();
});

$('#cancel').click(function (e) {
    $('#expandable').addClass('collapsed');
    $('#leaving').removeClass('expanded');
    e.preventDefault();
});

$("#child_wrapper").on('click', '.remove_date_field', function (e) {
    e.preventDefault();
    $(e.target).closest('tr').remove();
    return false;
});

// remove existing wrapper rows
$('#existing_wrapper').on('click', '.remove_date_field', function (e) {
    e.preventDefault();
    $(this).closest('tr').remove();
    return false;
});

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

// In the case of failed submission, iterate the children previously submitted
$(".js-old-child").each(function (index) {
    // Grab the data out of the data attributes
    var dob = $(this).data("dob");

    // Convert to useful formats - add_child_form partial should have validated these
    var dateObj = moment(dob, "YYYY-MM", true).format("MMM YYYY");
    var childKey = Math.random();

    var displayMonths = moment().diff(dob, 'months') % 12;
    var displayYears = moment().diff(dob, 'years');

    // Create and append new style columns
    var ageColumn = '<td class="age-col">' + displayYears + ' yr, ' + displayMonths + ' mo</td>';
    var dobColumn = '<td class="dob-col"><input name="children[' + childKey + '][dob]" type="hidden" value="' + dob + '" >' + dateObj + '</td>';
    var removeColumn = '<td class="remove-col"><button type="button" class="remove_date_field"><i class="fa fa-minus" aria-hidden="true"></i></button></td>';

    $(this).append(ageColumn);
    $(this).append(dobColumn);
    $(this).append(removeColumn);
});

// setup the age input
$("#addCarerAgeInput").ageInput();

// emit button clicked event
$("#add-carer-age").click(function (e) {
    e.preventDefault();
    $("#addCarerAgeInput").trigger('childInput:submitted');
});
