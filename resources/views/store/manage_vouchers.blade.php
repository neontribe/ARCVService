@extends('store.layouts.service_master')

@section('title', 'Voucher Manager')

@section('content')

    @include('store.partials.navbar', ['headerTitle' => 'Voucher Manager'])

    <div class="content">
        <form>
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
                <div class="warning">
                    @foreach( $family->getNoticeReasons() as $notices )
                    <p class="v-spaced">
                        <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
                        Warning: {{ $notices['count'] }} {{ str_plural($notices['entity'], $notices['count']) }}
                        is {{ $notices['reason'] }}
                    </p>
                    @endforeach
                </div>
                <button class="short-button" onclick="javascript:window.location.href='{{ URL::route("store.registration.edit", ['id' => $registration->id ]) }}'; return false;">
                  Go to edit family
                </button>
                <button class="long-button">Find another family</button>
            </div>
            <div class="col">
                <div>
                    <img src="{{ asset('store/assets/history-light.svg') }}">
                    <h2>Collection History</h2>
                </div>
                <div>
                    <p>This family should collect {{ $family->entitlement }} vouchers per week</p>
                    <p class="v-spaced">Their last collection was on Thursday 2nd August</p>
                </div>
                <div class="center">
                    <span id="brief-toggle" class="show clickable-span">
                      brief collection history
                      <i class="fa fa-caret-down" aria-hidden="true"></i>
                    </span>
                    <div id="brief" class="collapsed">
                        <table>
                            <tr>
                                <th>W/C</th>
                                <th>Vouchers collected</th>
                            </tr>
                            <tr>
                                <td>17/09/2018</td>
                                <td>0</td>
                            </tr>
                            <tr>
                                <td>10/09/2018</td>
                                <td>0</td>
                            </tr>
                        </table>
                    </div>
                </div>
                <button class="long-button">Full collection history</button>
            </div>
            <div class="col allocation">
                <div>
                    <img src="{{ asset('store/assets/allocation-light.svg') }}">
                    <h2>Allocate Vouchers</h2>
                </div>
                <div class="alongside-container">
                    <label>
                        First voucher
                        <input type="text">
                    </label>
                    <label>
                        Last voucher
                        <input id="last-voucher" type="text">
                    </label>
                    <button class="add-button">
                        <i class="fa fa-plus" aria-hidden="true"></i>
                    </button>
                </div>
                <p class="center">OR</p>
                <div>
                    <label for="add-voucher-input">Add individual vouchers</label>
                    <div class="small-button-container">
                        <input type="text" id="add-voucher-input">
                        <button class="addButton">
                            <i class="fa fa-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <p class="center vh-spaced">You have added # vouchers</p>
                <button id="collection-button" class="long-button">Go to voucher collection</button>
                <div class="center">
                  <span class="clickable-span">
                    Vouchers added
                    <i class="fa fa-caret-down" aria-hidden="true"></i>
                  </span>
                  <div id="vouchers" class="collapsed">
                      <table>
                          <tr>
                              <th>Voucher code</th>
                              <th>Remove</th>
                          </tr>
                          <tr>
                              <td>RVNT40500959</td>
                              <td><button><i class="fa fa-minus" aria-hidden="true"></i></button></td>
                          </tr>
                          <tr>
                              <td>RVNT40500959</td>
                              <td><button><i class="fa fa-minus" aria-hidden="true"></i></button></td>
                          </tr>
                      </table>
                  </div>
              </div>
            </div>
            <div id="collection" class="col collection-section">
                <div>
                    <img src="{{ asset('store/assets/collection-light.svg') }}">
                    <h2>Voucher Pick Up</h2>
                </div>
                <div>
                    <p>There are # vouchers waiting for the family</p>
                </div>
                <div class="pick-up vh-spaced">
                    <div>
                        <i class="fa fa-user"></i>
                        <div>
                            <label for="collected-by">Collected by:</label>
                            <select id="collected-by">
                                <option value="carer-1">Hester Johnson</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <i class="fa fa-calendar"></i>
                        <div>
                            <label for="collected-on">Collected on:</label>
                            {{-- set value to today's date with carbon --}}
                            <input id="collected-on" type="date">
                        </div>
                    </div>
                    <div>
                        <i class="fa fa-home"></i>
                        <div>
                            <label for="collected-at">Collected at:</label>
                            <select id="collected-at">
                              <option value="center-1">First Childrens Centre</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button class="short-button">Confirm pick up</button>
                <button class="long-button">Allocate more vouchers</button>
            </div>
        </form>
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

                $('.clickable-span').click(function (e) {
                    // the next sibling is the content
                    var content = $(this).next();
                    var isBriefToggle = $(this).is('#brief-toggle');

                    if(content.hasClass('collapsed')) {
                        content.removeClass('collapsed')
                        if (isBriefToggle) {
                            $(this).removeClass('show').addClass('hide');
                        }
                    } else {
                        content.addClass('collapsed');
                        if (isBriefToggle) {
                            $(this).removeClass('hide').addClass('show')
                        }
                    }
                });

                // Browser backup for lack of datepicker support eg. Safari
                // Reset back to English date format
                if ($('#collected-on')[0].type != 'date')
                    $('#collected-on').datepicker({ dateFormat: 'dd-mm-yy' }).val();
            }
        );
    </script>
@endsection
