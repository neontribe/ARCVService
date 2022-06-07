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

//remove invalid class when input is selected/tabbed to
$('#privacy-statement, #carer').on('click focus', function () {
    $(this).removeClass("invalid");
    // Remove relevent error message
    var spanclass = $(this)[0].id + '-span';
    $('#' + spanclass).addClass('collapsed');
});