@section('hoist-head')
    <script src="{{ asset('store/js/moment-2.20.1.min.js')}}"></script>
@endsection

<div class="col fit-height">
    <div>
        <img src="{{ asset('store/assets/pregnancy-light.svg') }}" alt="logo">
        <h2>Children or pregnancies</h2>
    </div>
    <div>
        <p>To add a child or pregnancy, complete the boxes below with their month and year of birth (or due date) in
            numbers, e.g. '06 2017' for June 2017.
        </p>
    </div>
    @include('store.partials.add_child_form', ['sponsorsRequiresID' => $sponsorsRequiresID])

    <div class="added">
        <table>
            <thead>
            <tr>
                <td class="age-col">Age</td>
                <td class="dob-col">Month / Year</td>
                @if ( $sponsorsRequiresID )
                    <td class="verified-col">ID</td>
                @endif
                @if (!empty($deferrable))
                    <td class="can-defer-col">Defer</td>
                @endif
                <td class="remove-col"></td>
            </tr>
            </thead>
            @if(!empty($children))
                <tbody id="existing_wrapper">
                @foreach ( $children as $child )
                    <tr>
                        <td class="age-col">{{ $child->getAgeString() }}</td>
                        <td class="dob-col">{{ $child->getDobAsString() }}</td>
                        @if ( $sponsorsRequiresID )
                            <td class="verified-col relative">
                                <input type="checkbox" class="styled-checkbox inline-dob"
                                       name="children[{{ $child->id }}][verified]"
                                       id="child{{ $child->id }}"
                                       {{ $child->verified ? "checked" : null }} value="1"
                                >
                                <label for="child{{ $child->id }}">
                                    <span class="visually-hidden">Toggle ID checked</span>
                                </label>
                            </td>
                        @endif
                        @if ( $deferrable )
                            <td class="can-defer-col relative">
                                @if ( $child->can_defer && $can_change_defer)
                                    <input type="checkbox"
                                           class="styled-checkbox inline-dob"
                                           name="children[{{ $child->id }}][deferred]"
                                           id="children[{{ $child->id }}][deferred]"
                                           {{ $child->deferred ? "checked" : null }} value="1"
                                    >
                                    <label for="children[{{ $child->id }}][deferred]">
                                        <span class="visually-hidden">Toggle canDefer checked</span>
                                    </label>
                                @elseif (isset($child->deferred) && !$can_change_defer)
                                    {{ $child->deferred ? 'Y' : 'N' }}
                                @endif
                            </td>
                        @endif
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
                    {{-- Whether or not the child was verified is not being sent back properly --}}
                    {{-- however, the key only turns up in the array when you checked the box --}}
                    {{-- so we'll go ahead and use that --}}
                    <tr class="js-old-child"
                        data-dob={{ $old_child['dob'] }} data-verified={{ isset($old_child['verified']) ? 1 : 0 }}></tr>
                @endforeach
            @endif
            </tbody>
        </table>
    </div>
</div>
@pushonce("bottom:family")
    <script>
        function addDobRow(e, dateObj, verified) {
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
                : '<td dusk="pregnancy_col" class="age-col">P</td>'
            ;

            var dobColumn = '<td class="dob-col"><input name="children[' + childKey + '][dob]" type="hidden" value="' + valueDate + '" >' + innerTextDate + '</td>';

            var idColumn = (verified !== null)
                ? '<td class="verified-col relative"><input type="checkbox" dusk="create_child_dob" class="styled-checkbox inline-dob" name="children[' + childKey + '][verified]" id="child' + childKey + '" ' + displayVerified + ' value="' + verifiedValue + '"><label for="child' + childKey + '"><span class="visually-hidden">Toggle ID checked</span></label>' + '</td>'
                : '';

            // add a defer column if we need to
            var deferColumn = ($('#added').find('td.can-defer-col').length > 0)
                ? '<td class="can-defer-col relative"><input type="checkbox" class="styled-checkbox inline-dob" name="children[' + childKey + '][deferred]" id="children[' + childKey + '][deferred]" value="0"><label for="children[' + childKey + '][deferred]"><span class="visually-hidden">Toggle canDefer checked</span></label></td>'
                : ''
            ;

            var removeColumn = '<td class="remove-col"><button type="button" class="remove_date_field"><i class="fa fa-minus" aria-hidden="true"></i></button></td>';

            // add an input
            $("#child_wrapper").append('<tr>' + ageColumn + dobColumn + idColumn + deferColumn + removeColumn + '</tr>');

            // emit event
            $(document).trigger('childRow:updated');
        }

        $(document).on('childInput:validated', addDobRow);
    </script>
@endpushonce
