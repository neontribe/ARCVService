@extends('store.layouts.service_master')

@section('title', 'Add a new Family')

@section('content')

    @include('store.partials.navbar', ['headerTitle' => 'New family sign up'])

    <div class="content">
        <form action="{{ URL::route("store.registration.store") }}" method="post" class="full-height">
            {!! csrf_field() !!}
            <div class="col">
                <div>
                    <img src="{{ asset('store/assets/group-light.svg') }}" alt="logo">
                    <h2>Voucher collectors</h2>
                </div>
                <div>
                    <label for="carer">Main carer's full name</label>
                    <input id="carer" name="carer" class="@if($errors->has('carer'))invalid @endif" type="text" autocomplete="off" autocorrect="off" spellcheck="false" value="{{ old('carer') }}">
                </div>
                @if ( $errors->has('carer') )
                <div class="alert-message error" id="carer-alert">
                    <div class="icon-container error">
                        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                    </div>
                    <div>
                        <p>This field is required</p>
                    </div>
                </div>
                @endif
                <div>
                    <label for="carer_adder_input">Other people who can collect <span>(optional)</span></label>
                    <div id="carer_adder" class="small-button-container">
                        <input id="carer_adder_input" name="carer_adder_input" type="text" autocomplete="off" autocorrect="off" spellcheck="false">
                        <button id="add_collector" class="add-button">
                            <i class="fa fa-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="added">
                    <label for="carer_wrapper">You have added: </label>
                    <table id="carer_wrapper">
                        @if(is_array(old('carers')) || (!empty(old('carers'))))
                            @foreach (old('carers') as $old_sec_carer )
                                <tr>
                                    <td><input name="carers[]" type="text" value="{{ $old_sec_carer }}" ></td>
                                    <td><button type="button" class="remove_field"><i class="fa fa-minus" aria-hidden="true"></i></button></td>
                                </tr>
                            @endforeach
                        @endif
                    </table>
                </div>
            </div>
            <div class="col">
                <div>
                    <img src="{{ asset('store/assets/pregnancy-light.svg') }}" alt="logo">
                    <h2>Children or pregnancies</h2>
                </div>
                <div>
                <p>To add a child or pregnancy, complete the boxes below with their month and year of birth (or due date) in numbers, e.g. '06 2017' for June 2017.
                </p>
                </div>
                @include('store.partials.add_child_form', ['verifying' => $verifying])
                <div class="added">
                    <label for="child_wrapper">You have added:</label>
                    <table>
                        <thead>
                            <tr>
                                <td class="age-col">Age</td>
                                <td class="dob-col">Month / Year</td>
                                @if ( $verifying )
                                <td class="verified-col">ID</td>
                                @endif
                                <td class="remove-col"></td>
                            </tr>
                        </thead>
                        <tbody id="child_wrapper">
                            @if(is_array(old('children')) || (!empty(old('children'))))
                                @foreach (old('children') as $old_child )
                                <tr class="js-old-child" data-dob={{ $old_child['dob'] }} data-verified={{ $old_child['verified'] ?? 0 }}></tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col">
                <div>
                    <img src="{{ asset('store/assets/info-light.svg') }}" alt="logo">
                    <h2>Other information</h2>
                </div>
                <div>
                    <label for="eligibility-hsbs">
                        Are you receiving Healthy Start or Best Start?
                    </label><br>
                    <select name="eligibility-hsbs" id="eligibility-hsbs">
                        <option value=0>Please select</option>
                        @foreach (config('arc.reg_eligibilities_hsbs') as $index => $reg_eligibility)
                            <option value="{{ $reg_eligibility }}"
                                    @if(
                                        (empty(old('eligibility-hsbs')) && $index === 0) ||
                                        old('eligibility-hsbs') === "healthy-start-receiving"
                                        ) selected="selected"
                                    @endif
                            >@lang('arc.reg_eligibilities_hsbs.' . $reg_eligibility)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="eligibility-nrpf">
                        No recourse to public funds family?
                    </label><br>
                    <select name="eligibility-nrpf" id="eligibility-nrpf">
                        <option value=0>Please select</option>
                        @foreach (config('arc.reg_eligibilities_nrpf') as $index => $reg_eligibility)
                            <option value="{{ $reg_eligibility }}"
                                    @if(
                                        (empty(old('eligibility-nrpf')) && $index === 0) ||
                                        old('eligibility-nrpf') === "healthy-start-receiving"
                                        ) selected="selected"
                                    @endif
                            >@lang('arc.reg_eligibilities_nrpf.' . $reg_eligibility)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="user-control">
                        <input type="checkbox" class="styled-checkbox @if($errors->has('consent'))invalid @endif" id="privacy-statement" name="consent" @if( old('consent') ) checked @endif/>
                        <label for="privacy-statement">Has the registration form been completed and signed?</label>
                    </div>
                </div>
                @if ( $errors->has('consent') )
                <div class="alert-message error" id="registration-alert">
                    <div class="icon-container error">
                        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                    </div>
                    <div>
                        <p>Registration form must be signed in order to complete registration</p>
                    </div>
                </div>
                @endif
                <button class="long-button submit" type="Submit">Save Family</button>
            </div>
        </form>
    </div>
    <script>
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
                        $(el).append('<tr><td><input name="carers[]" type="text" value="' + carer_el.val() + '" ></td><td><button type="button" class="remove_field"><i class="fa fa-minus" aria-hidden="true"></i></button></td></tr>');
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
    </script>

@endsection
