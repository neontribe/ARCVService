@extends('store.layouts.service_master')

@section('title', 'Voucher Manager')

@section('content')

    @include('store.partials.navbar', ['headerTitle' => 'Voucher Manager'])

    <div class="content">
        <div class="col-container">
            <div class="col">
                <div>
                    <img src="{{ asset('store/assets/info-light.svg') }}">
                    <h2>This Family</h2>
                </div>
                <div class="alongside-container">
                    <div>
                        <h3>Main Carer:</h3>
                        <p>{{ $pri_carer->name }}</p>
                    </div>
                    <div>
                        <h3>Children:</h3>
                        <ul>
                          @foreach ( $children as $child )
                            <li>{{ $child->getAgeString() }}</li>
                          @endforeach
                        </ul>
                    </div>
                </div>
                @if ( !empty($family->getNoticeReasons()) )
                <div class="alert-message warning">
                    <div class="icon-container warning">
                        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                    </div>
                    <div>
                        @foreach( $family->getNoticeReasons() as $notices )
                            <p class="v-spaced">
                                Warning: {{ $notices['count'] }} {{ str_plural($notices['entity'], $notices['count']) }}
                                is {{ $notices['reason'] }}
                            </p>
                        @endforeach
                    </div>
                </div>
                @endif
                <a href="{{ route("store.registration.edit", ['id' => $registration->id ]) }}" class="link">
                    <div class="link-button link-button-large">
                        <i class="fa fa-pencil button-icon" aria-hidden="true"></i>Go to edit family
                    </div>
                </a>
                <a href="{{ route("store.registration.index") }}" class="link">
                    <div class="link-button link-button-large">
                        <i class="fa fa-search button-icon" aria-hidden="true"></i>Find another family
                    </div>
                </a>
            </div>
            <div class="col">
                <div>
                    <img src="{{ asset('store/assets/history-light.svg') }}">
                    <h2>Collection History</h2>
                </div>
                <div>
                    <div class="emphasised-section">
                        <p>This family should collect:</p>
                        <p><b>{{ $family->entitlement }} vouchers per week</b></p>
                    </div>
                    @if (!empty($lastCollection))
                        <div class="emphasised-section">
                            <p>Their last collection was:</p>
                            <p><b>{{ $lastCollection }}</b></p>
                        </div>
                    @else
                        <div class="emphasised-section">
                            <p class="v-spaced">This family has not collected</p>
                        </div>
                    @endif
                </div>
                <a href="{{ route("store.registration.collection-history", ['id' => $registration->id ]) }}" class="link">
                    <div class="link-button link-button-large">
                        <i class="fa fa-clock-o button-icon" aria-hidden="true"></i>
                        Full Collection History
                    </div>
                </a>
            </div>
            <div class="col allocation">
                <div>
                    <img src="{{ asset('store/assets/allocation-light.svg') }}">
                    <h2>Allocate Vouchers</h2>
                </div>
                <form method="post" action="{{ route('store.registration.vouchers.post', [ 'registration' => $registration->id ]) }}">
                    {!! csrf_field() !!}
                    <div class="alongside-container">
                        <label>First voucher
                            <input id="first-voucher" name="start" type="text" autofocus>
                        </label>
                        <label>Last voucher
                            <input id="last-voucher" name="end" type="text">
                        </label>
                        <button id="range-add" class="add-button" type="submit">
                            <i class="fa fa-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                </form>
                <p class="center no-margin">OR</p>
                <form method="post" action="{{ route('store.registration.vouchers.post', [ 'registration' => $registration->id ]) }}">
                {!! csrf_field() !!}
                    <div class="single-container">
                        <label for="add-voucher-input">Add individual vouchers
                            <input id="add-voucher-input" name="start" type="text">
                        </label>
                        <button class="add-button" type="submit">
                            <i class="fa fa-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                </form>
                @if ( count( $errors ) > 0)
                <div class="alert-message error">
                    <div class="icon-container error">
                        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                    </div>
                    <div>
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}{{ Session::get('error_message') }}</p>
                        @endforeach
                    </div>
                </div>
                @endif
                @if (Session::get('error_message error'))
                <div class="alert-message">
                    <div class="icon-container error">
                        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                    </div>
                    <div>
                        <p>{{ Session::get('error_message') }}</p>
                    </div>
                </div>
                @endif
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
                                            <button type="submit" class="delete-button" formaction="{{ URL::route('store.registration.voucher.delete', ['registration' => $registration->id, 'voucher' => $voucher->id]) }}" id="{{ $voucher->id }}">
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
                    <p>There are {{ $vouchers_amount }} vouchers waiting for the family</p>
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
                <a href="{{ route("store.registration.voucher-manager", ['id' => $registration->id ]) }}" class="link">
                    <div class="link-button link-button-large">
                        <i class="fa fa-ticket button-icon" aria-hidden="true"></i>Change allocated vouchers
                    </div>
                </a>
            </div>
        </div>
    </div>

    @section('hoist-head')
        <script src="{{ asset('store/js/jquery-ui-1.12.1/jquery-ui.min.js')}}"></script>
    @endsection
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

                var firstVoucher = $('#first-voucher');
                var lastVoucher = $('#last-voucher');

                // Handle first in range of vouchers
                firstVoucher.keypress(function(e) {
                    if(e.keyCode==13){
                        var firstValue = firstVoucher.val();
                        if(firstValue !== ""){
                            lastVoucher.focus();
                        }
                        e.preventDefault();
                    }
                });

                // Handle last in range of vouchers
                lastVoucher.keypress(function(e) {
                    if(e.keyCode==13){
                        var firstValue = firstVoucher.val();
                        if(firstValue === "") {
                            firstVoucher.focus();
                            e.preventDefault();
                            return
                        }

                        var lastValue = lastVoucher.val();
                        if(firstValue !== "" && lastValue !== "") {
                            $('#range-add').trigger('click');
                            e.preventDefault();
                        }
                    }
                });

                // Browser backup for lack of datepicker support eg. Safari
                // Reset back to English date format
                if ($('#collected-on')[0].type != 'date')
                    $('#collected-on').datepicker({ dateFormat: 'dd-mm-yyyy' }).val();

                $('#collected-on').valueAsDate = new Date();
            }
        );
    </script>
@endsection
