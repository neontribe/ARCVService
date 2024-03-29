$(document).ready(
    function () {
        var maxFields = 10;
        var el = $("#carer_wrapper");
        var carer_el = $('#carer_adder_input');
        var fields = 1;
        var rxName = /^[ \w.'`—-]+$/

        /// add button click
        $("#add_collector").click(function (e) {
            e.preventDefault();
            var validateName = carer_el.val().match(rxName);
            if (carer_el.val().length <= 1 || validateName === null) {
                $('#carer-name-error').show();
                return false;
            }
            $('#carer-name-error').hide();
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
$('#pri_carer').on('keyup keypress', function (e) {
    if (e.which === 13) {
        e.preventDefault();
        document.activeElement.blur();
        $("input").blur();
        return false;
    }
});

//remove invalid class & error span when input is selected/tabbed to
$('#pri_carer').on('click focus', function () {
    $(this).removeClass("invalid");
    $('#carer-span').addClass('collapsed');
});

$('.clickable-span').click(function (e) {
    $('#more-family-info').removeClass('collapsed');
    e.preventDefault();
});

$('.clickable-span').click(function (e) {
    $('#more-ethnicity-info').removeClass('collapsed');
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


// emit button clicked event
$("#add-carer-age").click(function (e) {
    e.preventDefault();
    $("#addCarerAgeInput").trigger('childInput:submitted');
});

// remove existing wrapper rows
$('#existing_wrapper').on('click', '.remove_date_field', function (e) {
    e.preventDefault();
    $(this).closest('tr').remove();
    return false;
});
