@extends('store.layouts.service_master')

@section('title', 'Payment Request')

@section('content')

    <div class="content history payment">
        <div class="info">
        <h1>Payment Request</h1>
        <h2>{{ $trader }}<h2>
        </div>
        {{-- TODO: if uuid is valid && not expired --}}
        @if (true)
            <table>
                <tr>
                    <th>Voucher Code</th>
                    <th>Status</th>
                    <th>Date Paid</th>
                </tr>
                {{-- TODO: foreach voucher of same uuid --}}
                @foreach ($voucher_codes as $voucher)
                    <tr>
                        <td>
                            {{ $voucher }}
                        </td>
                        <td>
                            {{-- TO DO: get status from uuid --}}
                            <span>Paid</span>
                        </td>
                        <td>
                            {{-- get date of request from uuid --}}
                            18/12/2018
                        </td>
                    </tr>
                @endforeach
            </table>
            <div class="confirms">
                <a href="#" class="">
                    Cancel
                </a>
                <a href="#" class="link">
                    <div class="link-button link-button-large">
                        </i><i class="fa fa-money button-icon" aria-hidden="true"></i>Pay Vouchers
                    </div>
                </a>
            </div>
        @else
            <p class="content-warning centre">This payment request is invalid, or has expired.</p>
        @endif
    </div>

@endsection
