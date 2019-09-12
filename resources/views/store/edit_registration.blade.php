@extends('store.layouts.service_master')

@section('title', 'Check / Update Registration')

@section('content')

    @include('store.partials.navbar', ['headerTitle' => 'Check, update or print'])

    <div class="content flex">
        <form action="{{ URL::route("store.registration.update",['id' => $registration->id]) }}" method="post">
            {{ method_field('PUT') }}
            {!! csrf_field() !!}
            <input type="hidden" name="registration" value="{{ $registration->id }}">
            <div class="col fit-height">
                <div>
                    <img src="{{ asset('store/assets/group-light.svg') }}" name="logo">
                    <h2>Voucher collectors</h2>
                </div>
                <input type="hidden" name="registration" value="{{ $registration->id }}">
                <div>
                    <label for="carer">Main carer</label>
                    <span class="@if(!$errors->has('pri_carer'))collapsed @endif invalid-error" id="carer-span">This field is required</span>
                    <input id="carer" name="pri_carer[{{ $pri_carer->id }}]" class="@if($errors->has('pri_carer'))invalid @endif" type="text" value="{{ $pri_carer->name }}" autocomplete="off"
                        autocorrect="off" spellcheck="false">
                </div>
                <div>
                    <label for="carer_adder_input">Voucher collectors</label>
                    <table id="carer_wrapper">
                        @foreach ( $sec_carers as $sec_carer )
                            <tr>
                                <td>
                                    <input name="sec_carers[{{ $sec_carer->id }}]" type="text" value="{{ $sec_carer->name }}" >
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
                    <img src="{{ asset('store/assets/pregnancy-light.svg') }}" name="logo">
                    <h2>Children or pregnancy</h2>
                </div>
                <div>
                    <table>
                        <thead>
                        <tr>
                            <td>Age</td>
                            <td>Month / Year</td>
                            <td></td>
                        </tr>
                        </thead>
                        <tbody id="existing_wrapper">
                        @foreach ( $children as $child )
                            <tr>
                                <td>{{ $child->getAgeString() }}</td>
                                <td>{{ $child->getDobAsString() }}</td>
                                <td>
                                    <input type="hidden" name="children[]"
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
                    <label for="add-child-form">Add more children or a pregnancy:</label>
                    @include('store.partials.add_child_form')
                    <table>
                        <tbody id="child_wrapper">
                        </tbody>
                    </table>
                </div>
                <button class="long-button submit" type="submit">Save Changes</button>
                <a href="{{ route("store.registration.voucher-manager", ['id' => $registration->id ]) }}" class="link">
                    <div class="link-button link-button-large">
                        <i class="fa fa-ticket button-icon" aria-hidden="true"></i>Go to voucher manager
                    </div>
                </a>
            </div>

            <div class="col collect short-height">
                <div>
                    <img src="{{ asset('store/assets/info-light.svg') }}" name="logo">
                    <h2>This family</h2>
                </div>
                <div>
                    <p>This family:</p>
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
                            <p>Vouchers per week per child:</p>
                            <ul>
                                <li>Pregnancy - 3 vouchers</li>
                                <li>Birth up to 1 year - 6 vouchers</li>
                                <li>1 year up to school age - 3 vouchers</li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <div>
                    <p class="v-spaced">Their RV-ID is: <strong>{{ $family->rvid }}</strong></p>
                </div>
                @if ( !empty($noticeReasons) )
                <div class="alert-message warning">
                    <div class="icon-container warning">
                        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                    </div>
                    <div>
                        @foreach( $noticeReasons as $notices )
                            <p class="v-spaced">
                                Warning: {{ $notices['count'] }} {{ str_plural($notices['entity'], $notices['count']) }}
                                currently "{{ $notices['reason'] }}"</p>
                            </p>
                        @endforeach
                    </div>
                </div>
                @endif
                <button class="long-button" onclick="window.open( '{{ URL::route("store.registration.print", ["id" => $registration->id]) }}'); return false">
                    Print a 4 week collection sheet for this family
                </button>
            </div>
        </form>

        @if (!isset($registration->family->leaving_on) )
            <form  action="{{ URL::route('store.registration.family',['id' => $registration->id]) }}" method="post" id="leaving">
                {{ method_field('PUT') }}
                {!! csrf_field() !!}
                <div class="full-width flex-col">
                    <button class="remove long-button" type="button">Remove this family</button>
                    <div id="expandable" class="collapsed confirm-leaving" >
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
        $('#carer').on('click focus', function() {
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
