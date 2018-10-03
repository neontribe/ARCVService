@extends('store.layouts.service_master')

@section('title', 'Voucher Collection')

@section('content')

    @include('store.partials.navbar', ['headerTitle' => 'Voucher Collection'])

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
                        <p>Hester Johnson</p>
                    </div>
                    <div>
                        <h3>Children:</h3>
                        <ul>
                            <li>2 yr, 0 mo</li>
                            <li>Pregnancy</li>
                        </ul>
                    </div>
                </div>
                <div class="warning">
                    <p class="v-spaced">
                        <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
                        Next month a child has a birthday, so the voucher allocation will change.
                    </p>
                </div>
                <button class="short-button">Go to edit family</button>
                <button class="long-button">Find another family</button>
            </div>
            <div class="col">
                <div>
                    <img src="{{ asset('store/assets/history-light.svg') }}">
                    <h2>Collection History</h2>
                </div>
                <div>
                    <p>This family should collect 6 vouchers per week</p>
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
                <div id="expandable" class="collapsed">
                    <p># vouchers</p>
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
                        <label for="collected-by">Collected by:</label>
                        <select id="collected-by">
                          <option value="carer-1">Hester Johnson</option>
                        </select>
                    </div>
                    <div>
                        <i class="fa fa-calendar"></i>
                        <label for="collected-on">Collected on:</label>
                        {{-- set value to today's date with carbon --}}
                        <input id="collected-on" type="date">
                    </div>
                    <div>
                        <i class="fa fa-home"></i>
                        <label for="collected-at">Collected at:</label>
                        <select id="collected-at">
                          <option value="center-1">First Childrens Centre</option>
                        </select>
                    </div>
                </div>
                <button class="short-button">Confirm pick up</button>
                <button class="long-button">Allocate more vouchers</button>
            </div>
        </form>
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

                // Expander functionality for voucher history
                $('.clickable-span').click(function (e) {
                    // Collapsing/Expanding content
                    if($('#brief').hasClass('collapsed')) {
                        $('#brief').removeClass('collapsed');
                    } else {
                        $('#brief').addClass('collapsed');
                    }
                    // Show/hide and arrow
                    if($('#brief-toggle').hasClass('show')) {
                        $('#brief-toggle').removeClass('show');
                        $('#brief-toggle').addClass('hide');
                    } else {
                        $('#brief-toggle').removeClass('hide');
                        $('#brief-toggle').addClass('show');
                    }
                    e.preventDefault();
                });
            }
        );
    </script>
@endsection
