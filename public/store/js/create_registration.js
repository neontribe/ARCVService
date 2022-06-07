        $(document).ready(
            function () {
                var maxFields = 10;
                var el = $("#carer_wrapper");
                var carer_el = $('#carer_adder_input');
                var addButton = $("#add_collector");
                var fields = 1;
                $(addButton).click(function (e) {
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

                // In the case of failed submission, iterate the children previously submitted
                $(".js-old-child").each(function( index ) {
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

                $(el).on("click", ".remove_field", function (e) {
                    e.preventDefault();
                    $(this).closest('tr').remove();
                    fields--;
                })
            }
        );

        // If enter is pressed, keyboard is hidden on iPad and form submit is disabled
        $('#carer').on('keyup keypress', function(e) {
            if(e.which === 13) {
                e.preventDefault();
                document.activeElement.blur();
                $("input").blur();
                return false;
            }
        });
        //remove invalid class when input is selected/tabbed to
        $('#privacy-statement, #carer').on('click focus', function() {
            $(this).removeClass("invalid");
            // Remove relevent error message
            var spanclass = $(this)[0].id + '-span';
            $('#' + spanclass).addClass('collapsed');
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
