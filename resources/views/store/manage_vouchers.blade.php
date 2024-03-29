@extends('store.layouts.service_master')

@section('title', 'Voucher Manager')

@section('content')

    @include('store.partials.navbar', ['headerTitle' => 'Voucher Manager'])

    <div class="content">
        <div class="col-container">
            <div class="col">
                <div>
                    <img src="{{ $programme === 0 ? asset('store/assets/info-light.svg') : asset('store/assets/group-light.svg') }}">
                    <h2 id='this-family'>{{ $programme === 0 ? ' This Family' : 'Voucher Collectors' }}</h2>
                </div>
                <div>
                    <h3 id='rv-id'>Their RV-ID is: {{ $registration->family->rvid }}</h3>
                </div>
                <div class="alongside-container">
                    <div>
                        <h3>Main {{ $programme === 0 ? 'Carer' : 'Participant' }}</h3>
                        <p>{{ $pri_carer->name }}</p>
                    </div>
                    <div>
                        <h3>{{ $programme === 0 ? 'Children:' : 'Household' }}</h3>
                        <ul>
                          @foreach ( $children as $child )
                            <li>{{ $child->getAgeString() }}</li>
                          @endforeach
                        </ul>
                    </div>
                </div>
                @if ($programme === 0)
                    @includeWhen(!empty($noticeReasons), 'store.partials.notice_box', ['noticeReasons' => $noticeReasons])
                @endif
                <a href="{{ route("store.registration.edit", ['registration' => $registration->id ]) }}" class="link" id='edit-family-link'>
                    <div class="link-button link-button-large">
                        <i class="fa fa-pencil button-icon" aria-hidden="true"></i>Go to edit {{ $programme === 0 ? 'family' : 'household' }}
                    </div>
                </a>
                <a href="{{ route("store.registration.index") }}" class="link" id='find-another-family-link'>
                    <div class="link-button link-button-large">
                        <i class="fa fa-search button-icon" aria-hidden="true"></i>Find another {{ $programme === 0 ? 'family' : 'household' }}
                    </div>
                </a>
            </div>
            <div class="col">
                <div>
                    <img src="{{ asset('store/assets/history-light.svg') }}">
                    <h2 id='collection-history'>Collection History</h2>
                </div>
                <div>
                    <div class="emphasised-section">
                        <p>This {{ $programme === 0 ? 'family' : 'household' }} should collect:</p>
                        <p><b>{{ $entitlement }} vouchers per week</b></p>
                    </div>
                    @if (!empty($lastCollection))
                        <div class="emphasised-section">
                            <p>Their last collection was:</p>
                            <p><b>{{ $lastCollection }}</b></p>
                        </div>
                    @else
                        <div class="emphasised-section">
                            <p class="v-spaced">This {{ $programme === 0 ? 'family' : 'household' }} has not collected</p>
                        </div>
                    @endif
                </div>
                <a href="{{ route("store.registration.collection-history", ['registration' => $registration->id ]) }}" class="link" id='full-collection-link'>
                    <div class="link-button link-button-large">
                        <i class="fa fa-clock-o button-icon" aria-hidden="true"></i>
                        Full Collection History
                    </div>
                </a>
            </div>
            <div class="col allocation">
                <div>
                    <img src="{{ asset('store/assets/allocation-light.svg') }}">
                    <h2 id='allocate-vouchers'>Allocate Vouchers</h2>
                </div>
                <form method="post" action="{{ route('store.registration.vouchers.post', [ 'registration' => $registration->id ]) }}">
                    {!! csrf_field() !!}
                    <div class="alongside-container">
                        <label>First voucher
                            <input id="first-voucher" name="start" type="text" autofocus class="uppercase" autocomplete="off">
                        </label>
                        <label>Last voucher
                            <input id="last-voucher" name="end" type="text" class="uppercase" autocomplete="off">
                        </label>
                        <button id="range-add" class="add-button" type="submit" name="range-add">
                            <i class="fa fa-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                </form>
                <p class="center no-margin">OR</p>
                <form method="post" action="{{ route('store.registration.vouchers.post', [ 'registration' => $registration->id ]) }}">
                {!! csrf_field() !!}
                    <div class="single-container">
                        <label for="single-voucher">Add individual vouchers
                            <input id="single-voucher" name="start" type="text" class="uppercase" autocomplete="off">
                        </label>
                        <button id="single-add" class="add-button" type="submit" name="add-button">
                            <i class="fa fa-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                </form>
                @includeWhen($errors->count() > 0, 'store.partials.errors', ['error_array' => $errors->all()])
                @includeWhen(Session::get('error_messages'), 'store.partials.errors', ['error_array' => Session::get('error_messages')])
                <button id="collection-button"
                        class="long-button"
                        @if ($vouchers_amount == 0)
                            disabled
                        @endif
                >
                    <i class="fa fa-ticket button-icon" aria-hidden="true"></i>Go to voucher collection</button>
                <div class="center" id="vouchers-added">
                    <span class="emphasised-section">Vouchers added</span>
                    <span class="number-circle">{{ $vouchers_amount }}</span>
                    <div class="@if($vouchers_amount === 0)collapsed @endif">
                        <form id="unbundle-all" name="unbundle-all" action="" method="POST" class="delete-button remove-all-container">
                            {!! method_field('delete') !!}
                            {!! csrf_field() !!}
                            <button type="submit" formaction="{{ URL::route('store.registration.vouchers.delete', ['registration' => $registration->id ]) }}" name="delete-all-button" id="delete-all-button">
                                <i class="fa fa-trash" aria-hidden="true"></i>  Remove all added vouchers
                            </button>
                        </form>
                    </div>
                    <div id="vouchers" class="@if($vouchers_amount === 0)collapsed @endif">
                        <form id="unbundle" name="unbundle" action="" method="POST">
                            {!! method_field('delete') !!}
                            {!! csrf_field() !!}
                            <table>
                                <tr>
                                    <th>Voucher code</th>
                                    <th>Remove</th>
                                </tr>
                                @foreach( $vouchers as $voucher )
                                    <tr>
                                        <td>{{ $voucher->code }}</td>
                                        <td>
                                            <button type="submit" class="delete-button" name="delete-button" formaction="{{ URL::route('store.registration.voucher.delete', ['registration' => $registration->id, 'voucher' => $voucher->id]) }}" id="{{ $voucher->id }}">
                                                <i class="fa fa-minus" aria-hidden="true"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </form>
                    </div>
                </div>
            </div>
            <div id="collection" class="col collection-section">
                <div>
                    <img src="{{ asset('store/assets/collection-light.svg') }}">
                    <h2>Voucher Pick Up</h2>
                </div>
                <div>
                    <p>There are {{ $vouchers_amount }} vouchers waiting for this {{ $programme === 0 ? 'family' : 'household' }}</p>
                </div>
                <form method="post"
                      action="{{ route('store.registration.vouchers.put', [ 'registration' => $registration->id ]) }}">
                    {!! method_field('put') !!}
                    {!! csrf_field() !!}
                    <div class="pick-up">
                        <div>
                            <i class="fa fa-user"></i>
                            <div>
                                <label for="collected-by">Collected by:</label>
                                <select id="collected-by" name="collected_by">
                                    @foreach( $carers as $carer )
                                        <option value="{{ $carer->id }}">{{ $carer->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <i class="fa fa-calendar"></i>
                            <div>
                                <label for="collected-on">Collected on:</label>
                                <div id="dateError" style="display:none;"></div>
                                <input id="collected-on" name="collected_on" value="<?php echo date('Y-m-d');?>" type="date">
                            </div>
                        </div>
                        <div>
                            <i class="fa fa-home"></i>
                            <div>
                                <label for="collected-at">Collected at: {{ $centre->name }}</label>
                                <input type="hidden" id="collected-at" name="collected_at" value="{{ $centre->id }}" />
                            </div>
                        </div>
                        <button class="long-button submit"
                                type="submit"
                                @if ($vouchers_amount == 0)
                                    disabled
                                @endif
                        >
                            Confirm pick up
                        </button>
                    </div>
                </form>
                <a href="{{ route("store.registration.voucher-manager", ['registration' => $registration->id ]) }}" class="link">
                    <div class="link-button link-button-large">
                        <i class="fa fa-ticket button-icon" aria-hidden="true"></i>Change allocated vouchers
                    </div>
                </a>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        $(document).ready(
            function () {
                // Voucher collection section animations
                $('#collection-button').click(function (e) {
                    $('#collection').addClass('slide-in');
                    $('.allocation').addClass('fade-back');
                    e.preventDefault();
                });

                var vouchersInList = $('#vouchers tr').length;
                if(vouchersInList > 1) { // the first tr contains the table head
                    $('#vouchers-added').addClass('pulse');
                }

                // Allocate voucher input elements
                var firstVoucher = $('#first-voucher');
                var lastVoucher = $('#last-voucher');
                var singleVoucher = $('#single-voucher');

                var delay = 200;

                // For each input field, we listen for the carriage return added to the end of a code by a scanner
                // We delay the following action to prevent premature scanner input

                // Handle first in a range of vouchers
                firstVoucher.keypress(function(e) {
                    if(e.keyCode===13){
                        e.preventDefault();
                        window.setTimeout(function() {
                            if(firstVoucher.val() !== ""){
                                lastVoucher.focus();
                            }
                        }, delay);
                    }
                });

                // Handle last in a range of vouchers
                lastVoucher.keypress(function(e) {
                    if(e.keyCode===13){
                        e.preventDefault();
                        window.setTimeout(function() {
                            if(firstVoucher.val() === "") {
                                firstVoucher.focus();
                                return
                            }
                            if(lastVoucher.val() !== "") {
                                $('#range-add').trigger('click');
                            }
                        }, delay);
                    }
                });

                // Handle a single voucher
                singleVoucher.keypress(function(e) {
                    if(e.keyCode===13){
                        e.preventDefault();
                        window.setTimeout(function() {
                            $('#single-add').trigger('click');
                        }, delay);
                    }
                });

                // Browser backup for lack of datepicker support eg. IE11
                // Reset back to English date format
                var collectedOn = $('#collected-on');
                if (collectedOn[0].type !== 'date') {
                    collectedOn.datepicker({dateFormat: 'yy-mm-dd'}).val();
                }
                collectedOn.valueAsDate = new Date();

                $("#collected-on").change(function() {
                  var chosenDate = $(this).val();
                  var chosenDateJS = new Date(chosenDate);
                  var sixWeeks = new Date();
                  sixWeeks.setDate(sixWeeks.getDate() + 42);
                  if (chosenDateJS > sixWeeks) {
                    $('#dateError').text('Please choose a date within six weeks of today');
                    $('#dateError').css('font-size', '12px');
                    $('#dateError').css('color', 'red');
                    $('#dateError').show();
                  } else {
                    $('#dateError').hide();
                  }
                });
            }
        );
    </script>
@endsection
