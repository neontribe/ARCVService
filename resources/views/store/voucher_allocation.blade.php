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
                <div id="expandable">
                    <a class="center">Brief collection history &#x25BC;</a>
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
                        <p>Collected by Hester Johnson</p>
                        <button>Change</button>
                    </div>
                    <div>
                        <p>Collected today, 20th August</p>
                        <button>Change</button>
                    </div>
                    <div>
                        <p>Collected at 1st place Children's Centre</p>
                        <button>Change</button>
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
                $('#collection-button').click(function (e) {
                    $('#collection').addClass('slide-in');
                    $('.allocation').addClass('fade-back');
                    e.preventDefault();
                });
            }
        );
    </script>
@endsection