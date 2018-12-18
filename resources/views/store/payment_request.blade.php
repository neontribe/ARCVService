@extends('store.layouts.service_master')

@section('title', 'Voucher Manager')

@section('content')

    @include('store.partials.navbar', ['headerTitle' => 'Payment Request'])

    <div class="content history payment">
        <div class="info">
          <h3>*Trader Name*</h3>
        </div>
        {{-- TODO: if uuid is valid && not expired --}}
        @if (!empty($bundles_by_week))
            <table>
                <tr>
                    <th>Voucher Code</th>
                    <th>Status</th>
                    <th>Date Paid</th>
                </tr>
                {{-- TODO: foreach voucher of same uuid --}}
                @foreach ($bundles_by_week as $week => $bundle)
                    <tr class="@if(!$bundle)disabled @endif">
                        <td>
                            RVNT0050
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
            <a href="{{ route("store.registration.voucher-manager", ['id' => $registration->id ]) }}" class="link">
                <div class="link-button link-button-large">
                    </i><i class="fa fa-money button-icon" aria-hidden="true"></i>Pay Vouchers
                </div>
            </a>
            </div>
        @else
            <p class="content-warning centre">This payment request is invalid, or has expired.</p>
        @endif
    </div>
s
@endsection
