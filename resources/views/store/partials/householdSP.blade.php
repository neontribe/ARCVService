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
                <td class="is-pri-carer-col"></td>
                <td class="remove-col"></td>
            </tr>
            </thead>
            @if(!empty($children))
                <tbody id="existing_wrapper">
                @foreach ( $children as $child )
                    <tr>
                        <td class="age-col">{{ explode(',', $child->getAgeString())[0] }}</td>
                        <td class="dob-col"></td>
                        <td class="is-pri-carer-col"><input type="hidden" name="children[{{ $child->id }}][is_pri_carer]"
                               value="{{ $child->is_pri_carer }}"
                        ></td>
                        <td class="remove-col">
                            <input type="hidden" name="children[{{ $child->id }}][dob]"
                                   value="{{ Carbon\Carbon::parse($child->dob)->format('Y-m') }}"
                            >
                            <button class="remove_date_field">
                                <i class="fa fa-minus" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            @endif
        </table>
        <table>
            <tbody id="child_wrapper">
            @if(is_array(old('children')) || (!empty(old('children'))))
                @foreach (old('children') as $old_child )
                    <tr class="js-old-child"
                        data-is_pri_carer={{ $old_child['is_pri_carer'] }}
                        data-dob={{ $old_child['dob'] }}
                    ></tr>
                @endforeach
            @endif
            </tbody>
        </table>
    </div>
</div>

@pushonce('bottom:householdSP')
    <script>
        function addAgeRow(e, dateObj, verified, buttonID) {
            // set a default
            if (typeof buttonID !== 'string') {
                buttonID = '';
            }
            // setup fields
            // It's a valid date, so manufacture a human-readable string
            var valueDate = dateObj.format("YYYY-MM");

            // give the kids a random key
            var childKey = Math.random();

            // Create the table columns
            var ageColumn = (moment().diff(valueDate, 'days') > 0)
                ? '<td class="age-col">' +
                moment().diff(valueDate, 'years') + ' yr</td>'
                : '<td class="age-col">P</td>'
            ;

            var isPriCarer = buttonID === 'addCarerAgeInput' ? 1 : 0;
            if (isPriCarer === 1) {
                $('.is-pri-carer-col').find("input").each(function() {
                    if ($(this).val() == 1) {
                        $(this).closest('tr').remove();
                    }
                });
            }

            var dobColumn = '<td class="dob-col"><input name="children[' + childKey + '][dob]" type="hidden" value="' + valueDate + '" ></td>';
            var isPriCarerColumn = '<td class="is-pri-carer-col"><input name="children[' + childKey + '][is_pri_carer]" type="hidden" value=' + isPriCarer + '></td>';
            var removeColumn = '<td class="remove-col"><button type="button" class="remove_date_field"><i class="fa fa-minus" aria-hidden="true"></i></button></td>';

            // add an input
            $("#child_wrapper").append('<tr>' + ageColumn + dobColumn + isPriCarerColumn + removeColumn + '</tr>');

            // emit event
            $(document).trigger('childRow:updated');
        }
        $(document).on('childInput:validated', addAgeRow);

        // In the case of failed submission, iterate the children previously submitted
        $(".js-old-child").each(function (index) {
            // Grab the data out of the data attributes
            var dob = $(this).data("dob");
            var isPriCarer = $(this).data("is_pri_carer");
            // Convert to useful formats - add_child_form partial should have validated these
            var childKey = Math.random();

            var displayYears = moment().diff(dob, 'years');

            // Create and append new style columns
            var ageColumn = '<td class="age-col">' + displayYears + ' yr</td>';
            var dobColumn = '<td class="dob-col"><input name="children[' + childKey + '][dob]" type="hidden" value="' + dob + '" ></td>';
            var isPriCarerColumn = '<td class="is-pri-carer-col"><input name="children[' + childKey + '][is_pri_carer]" type="hidden" value=' + isPriCarer + '></td>';
            var removeColumn = '<td class="remove-col"><button type="button" class="remove_date_field"><i class="fa fa-minus" aria-hidden="true"></i></button></td>';

            $(this).append(ageColumn);
            $(this).append(dobColumn);
            $(this).append(isPriCarerColumn);
            $(this).append(removeColumn);
        });
    </script>
@endpushonce
