@extends('store.layouts.service_master')

@section('title', 'Check / Update Registration')

@section('content')

    @include('store.partials.navbar', ['headerTitle' => 'Check, update or print'])

    <div class="content check">
        <form action="{{ URL::route("store.registration.update",['id' => $registration->id]) }}" method="post">
            {{ method_field('PUT') }}
            {!! csrf_field() !!}
            <input type="hidden" name="registration" value="{{ $registration->id }}">
            <div class="col">
                <div>
                    <img src="{{ asset('store/assets/group-light.svg') }}" name="logo">
                    <h2>Voucher collectors</h2>
                </div>
                <div>
                    <label for="carer">Main carer</label>
                    <span class="@if(!$errors->has('carer'))collapsed @endif invalid-error" id="carer-span">This field is required</span>
                    <input id="carer" name="carer[{{ $pri_carer->id }}]" class="@if($errors->has('carer'))invalid @endif" type="text" value="{{ $pri_carer->name }}" autocomplete="off"
                           autocorrect="off" spellcheck="false">
                </div>
                <div>
                    <label for="carer_adder_input">Voucher collectors</label>
                    <table id="carer_wrapper">
                        @foreach ( $sec_carers as $sec_carer )
                            <tr>
                                <td><input name="sec_carers[{{ $sec_carer->id }}]" type="text"
                                           value="{{ $sec_carer->name }}" ></td>
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

            <div class="col">
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
                        </i><i class="fa fa-ticket button-icon" aria-hidden="true"></i>Go to voucher manager
                    </div>
                </a>
            </div>
            <div class="col collect">
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
                                {{ $family->entitlement }}
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
                <p>Their RV-ID is: <strong>{{ $family->rvid }}</strong></p>
                <div class="warning">
                    @foreach( $family->getNoticeReasons() as $notices )
                        <p><i class="fa fa-exclamation-circle" aria-hidden="true"></i>
                            Warning: {{ $notices['count'] }} {{ str_plural($notices['entity'], $notices['count']) }}
                            currently "{{ $notices['reason'] }}"</p>
                    @endforeach
                </div>
                <div class="attention">
                    @if ( (Auth::user()->cannot('updateChart', App\Registration::class)) || (Auth::user()->cannot('updateDiary', App\Registration::class)) || (Auth::user()->cannot('updatePrivacy', App\Registration::class)))
                        @if ( count($registration->getReminderReasons()) > 0 )
                            <h3><i class="fa fa-exclamation-circle" aria-hidden="true"></i> Reminder</h3>
                            @foreach ( $registration->getReminderReasons() as $reminder )
                                <p>{{ $reminder['entity'] }} has {{ $reminder['reason'] }}</p>
                            @endforeach
                        @endif
                    @endif
                    @if ( (Auth::user()->can('updateChart', App\Registration::class)) || (Auth::user()->can('updateDiary', App\Registration::class)) || (Auth::user()->can('updatePrivacy', App\Registration::class)) )
                        <div>
                            <h2>Documents Received:</h2>
                            @can( 'updateChart', App\Registration::class )
                                <div class="user-control">
                                    <input type="hidden" name="fm_chart" value="0">
                                    <input type="checkbox" class="styled-checkbox" id="update-chart" name="fm_chart" value="1"
                                        @if( old('fm_chart') || isset($registration->fm_chart_on) ) checked @endif/>
                                    <label for="update-chart">Chart</label>
                                </div>
                            @endcan
                            @can( 'updateDiary', App\Registration::class )
                                <div class="user-control">
                                    <input type="hidden" name="fm_diary" value="0">
                                    <input type="checkbox" class="styled-checkbox" id="update-diary" name="fm_diary" value="1"
                                        @if( old('fm_diary') || isset($registration->fm_diary_on) ) checked @endif/>
                                    <label for="update-diary">Diary</label>
                                </div>
                            @endcan
                            @can( 'updatePrivacy', App\Registration::class )
                            <div class="user-control">
                                <input type="hidden" name="fm_privacy" value="0">
                                <input type="checkbox" class="styled-checkbox" id="update-privacy" name="fm_privacy" value="1"
                                    @if( old('fm_privacy') || isset($registration->fm_privacy_on) ) checked @endif/>
                                <label for="update-privacy">Privacy Statement</label>
                            </div>
                            @endcan
                        </div>
                    @endif
                </div>
                    <button class="long-button" onclick="window.open( '{{ URL::route("store.registration.print", ["id" => $registration->id]) }}'); return false">
                        Print a 4 week collection sheet for this family
                    </button>
            </div>
        </form>
        @if (!isset($registration->family->leaving_on) )
        <form class="leaving" action="{{ URL::route('store.registration.family',['id' => $registration->id]) }}" method="post">
            {{ method_field('PUT') }}
            {!! csrf_field() !!}
            <div>
                <button class="remove" type="button">Remove this family</button>
                <div id="expandable" class="collapsed" >
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
            e.preventDefault();
        });

        $('#cancel').click(function (e) {
            $('#expandable').addClass('collapsed');
            e.preventDefault();
        });

    </script>
@endsection
