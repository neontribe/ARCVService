@extends('store.layouts.service_master')

@section('title', 'Check / Update Registration')

@section('content')

    @include('store.partials.navbar', ['headerTitle' => 'Check, update or print'])

    <div class="content flex">
        <form action="{{ URL::route("store.registration.update",['registration' => $registration]) }}" method="post">
            {{ method_field('PUT') }}
            {!! csrf_field() !!}
            <input type="hidden" name="registration" value="{{ $registration->id }}">
            <div class="col fit-height">
                <div>
                    <img src="{{ asset('store/assets/group-light.svg') }}" alt="logo">
                    <h2>Voucher collectors</h2>
                </div>
                <input type="hidden" name="registration" value="{{ $registration->id }}">
                <div>
                    <label for="carer">Main carer</label>
                    <span class="@if(!$errors->has('pri_carer'))collapsed @endif invalid-error" id="carer-span">This field is required</span>
                    <input id="carer" name="pri_carer[{{ $pri_carer->id }}]"
                           class="@if($errors->has('pri_carer'))invalid @endif" type="text"
                           value="{{ $pri_carer->name }}" autocomplete="off"
                           autocorrect="off" spellcheck="false">
                </div>
                <div>
                    <label for="carer_adder_input">Voucher collectors</label>
                    <table id="carer_wrapper">
                        @foreach ( $sec_carers as $sec_carer )
                            <tr>
                                <td>
                                    <input name="sec_carers[{{ $sec_carer->id }}]" type="text"
                                           value="{{ $sec_carer->name }}">
                                </td>
                                <td>
                                    <button type="button" class="remove_field">
                                        <i class="fa fa-minus" aria-hidden="true"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                <div>
                    <label for="carer_adder_input">Add new collectors:</label>
                    <div id="carer_adder" class="small-button-container">
                        <input id="carer_adder_input" name="carer_adder_input" type="text" autocomplete="off"
                               autocorrect="off" spellcheck="false">
                        <button id="add_collector" class="add-button">
                            <i class="fa fa-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col fit-height">
                <div>
                    <img src="{{ asset('store/assets/pregnancy-light.svg') }}" alt="logo">
                    <h2>Children or pregnancy</h2>
                </div>
                @include('store.partials.add_child_form', ['verifying' => $verifying] )
                <div>
                    <table>
                        <thead>
                        <tr>
                            <td class="age-col">Age</td>
                            <td class="dob-col">Month / Year</td>
                            @if ( $verifying )
                            <td class="verified-col">ID</td>
                            @endif
                            @if ( $is_scottish )
                            <td class="can-defer-col">Defer</td>
                            @endif
                            <td class="remove-col"></td>
                        </tr>
                        </thead>
                        <tbody id="existing_wrapper">
                        @foreach ( $children as $child )
                            <tr>
                                <td class="age-col">{{ $child->getAgeString() }}</td>
                                <td class="dob-col">{{ $child->getDobAsString() }}</td>
                                @if ( $verifying )
                                <td class="verified-col relative"><input type="checkbox" class="styled-checkbox inline-dob" name="children[{{ $child->id }}][verified]" id="child{{ $child->id }}" {{ $child->verified ? "checked" : null }} value="1"><label for="child{{ $child->id }}"><span class="visually-hidden">Toggle ID checked</span></label></td>
                                @endif
                                @if ( $child->can_defer && $can_change_defer)
                                  <td class="can-defer-col relative"><input type="checkbox" class="styled-checkbox inline-dob" name="children[{{ $child->id }}][deferred]" id="children[{{ $child->id }}][deferred]" {{ $child->deferred ? "checked" : null }} value="1"><label for="children[{{ $child->id }}][deferred]"><span class="visually-hidden">Toggle canDefer checked</span></label></td>
                                @elseif ($child->deferred && !$can_change_defer)
                                  <td>{{ $child->deferred ? 'Y' : 'N' }}</td>
                                @else
                                  <td></td>
                                @endif
                                <td class="remove-col">
                                    <input type="hidden" name="children[{{ $child->id }}][dob]"
                                        value="{{ Carbon\Carbon::parse($child->dob)->format('Y-m') }}">
                                    <button class="remove_date_field">
                                        <i class="fa fa-minus" aria-hidden="true"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div>
                    <table>
                        <tbody id="child_wrapper">
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col collect short-height">
                <div>
                    <img src="{{ asset('store/assets/info-light.svg') }}" alt="logo">
                    <h2>This family</h2>
                </div>
                <div>
                    <p class="v-spaced">Their RV-ID is: <strong>{{ $family->rvid }}</strong></p>
                    <ul>
                        <li>
                            Should collect
                            <strong>
                                {{ $entitlement }}
                            </strong>
                            per week
                        </li>
                        <li>
                            Has
                            <strong>
                                {{ count($family->children) }}
                            </strong>
                            {{ str_plural('child', count($family->children)) }}
                            registered
                            @if ( $family->expecting != null )
                                including one pregnancy
                            @endif
                            <span class="clickable-span">(more)</span>
                        </li>
                        <li class="collapsed" id="more-family-info">
                            <p>The system gives these vouchers per week:</p>
                            <ul id="creditables">
                                @foreach($evaluations["creditables"] as $creditable)
                                    <li>
                                        If {{ strtolower(class_basename($creditable::SUBJECT)) }}
                                        {{ $creditable->reason }} :
                                        {{ $creditable->value }} {{ str_plural('voucher', $creditable->value) }}
                                    </li>
                                @endforeach
                            </ul>
                            @if(count($evaluations["disqualifiers"]) > 0)
                                <p>Reminders:</p>
                                <ul id="disqualifiers">
                                    @foreach($evaluations["disqualifiers"] as $disqualifier)
                                        <li>
                                            If {{ strtolower(class_basename($disqualifier::SUBJECT)) }} {{ $disqualifier->reason }} {{ $disqualifier->value}} {{ str_plural('voucher', $disqualifier->value) }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    </ul>
                </div>
                <div>
                    <label for="eligibility-hsbs">
                        Are you receiving Healthy Start or Best Start?
                    </label>
                    <select name="eligibility-hsbs" id="eligibility-hsbs">
                        @foreach (config('arc.reg_eligibilities_hsbs') as $reg_eligibility)
                            <option value="{{ $reg_eligibility }}"
                                    @if($registration->eligibility_hsbs == $reg_eligibility) selected="selected" @endif
                            >@lang('arc.reg_eligibilities_hsbs.' . $reg_eligibility)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="eligibility-nrpf">
                        No recourse to public funds family?
                    </label>
                    <select name="eligibility-nrpf" id="eligibility-nrpf">
                        @foreach (config('arc.reg_eligibilities_nrpf') as $reg_eligibility)
                            <option value="{{ $reg_eligibility }}"
                                    @if($registration->eligibility_nrpf == $reg_eligibility) selected="selected" @endif
                            >@lang('arc.reg_eligibilities_nrpf.' . $reg_eligibility)
                            </option>
                        @endforeach
                    </select>
                </div>
                @includeWhen(!empty($noticeReasons), 'store.partials.notice_box', ['noticeReasons' => $noticeReasons])
                <button class="long-button submit" type="submit">Save Changes</button>
                <div><hr class="col-break"></div>
                <button class="long-button"
                        onclick="window.open( '{{ URL::route("store.registration.print", ["registration" => $registration]) }}'); return false">
                    Print a 4 week collection sheet for this family
                </button>
                <a href="{{ route("store.registration.voucher-manager", ['registration' => $registration ]) }}" class="link">
                    <div class="link-button link-button-large">
                        <i class="fa fa-ticket button-icon" aria-hidden="true"></i>Go to voucher manager
                    </div>
                </a>
            </div>
        </form>

        @if (!isset($registration->family->leaving_on) )
            <form action="{{ URL::route('store.registration.family',['registration' => $registration]) }}" method="post"
                  id="leaving">
                {{ method_field('PUT') }}
                {!! csrf_field() !!}
                <div class="full-width flex-col">
                    <button class="remove long-button" type="button">Remove this family</button>
                    <div id="expandable" class="collapsed confirm-leaving">
                        <div class="reason">
                            <label for="reason-for-leaving">
                                Reason for leaving
                            </label>
                            <select id="reason-for-leaving" name="leaving_reason" required>
                                <option value="" disabled selected>Select a reason...</option>
                                @foreach(Config::get('arc.leaving_reasons') as $reason)
                                    <option value="{{ $reason }}"> {{ $reason }}</option>
                                @endforeach
                            </select>
                        </div>
                        <p>Are you sure?</p>
                        <div class="confirmation-buttons">
                            <button type="submit" class="submit">Yes</button>
                            <button id="cancel">Cancel</button>
                        </div>
                    </div>
                </div>
            </form>
        @endif
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

        $(document).ready(
            function () {
                var el = $("#existing_wrapper");
                $(el).on("click", ".remove_date_field", function (e) {
                    e.preventDefault();
                    $(this).closest('tr').remove();
                    return false;
                });
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
            $(".short-height").css("height", "47vh");
            e.preventDefault();
        });

        $('#cancel').click(function (e) {
            $('#expandable').addClass('collapsed');
            $('#leaving').removeClass('expanded');
            $(".short-height").css("height", "67vh");
            e.preventDefault();
        });

    </script>
@endsection
