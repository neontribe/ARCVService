@extends('service.layouts.app')

@section('title', 'Payment Request')

@section('content')

    <div id="container">

        @include('service.includes.sidebar')

        <div class="content payment" id="main-content">
            <h1>Payment Request</h1>
            @if ( $state_token !== null )
                <h3> {{ $trader }} </h3>
                <table class="table table-striped">
                    <tr>
                        <th>
                            Voucher Code
                        </th>
                        <th>
                            Status
                        </th>
                        <th>
                            {{-- TODO: conditionally toggle this between Date Paid, Date Requested or Date if there's a mix --}}
                            Date
                        </th>
                    </tr>
                    @foreach ( $vouchers as $voucher )
                        <tr>
                            <td>
                                {{ $voucher->code }}
                            </td>
                            @if ( $voucher->currentstate === "payment_pending" )
                                <td>
                                    <span class="status requested">Requested</span>
                                </td>
                                <td>
                                    {{ \Carbon\Carbon::parse($voucher->paymentPendedOn()->first()->created_at)->format('d/m/Y') }}
                                </td>
                            @elseif ( $voucher->currentstate === "reimbursed" )
                                <td>
                                    <span class="status paid">Paid</span>
                                </td>
                                <td>
                                    {{ \Carbon\Carbon::parse($voucher->reimbursedOn()->first()->created_at)->format('d/m/Y') }}
                                </td>
                            @else
                                {{-- We shouldn't encounter this state, but display the state in case we do --}}
                                <td>
                                    <span> {{ $voucher->currentstate }} </span>
                                </td>
                                <td>
                                    -
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </table>
                @if ( $number_to_pay > 0 )
                    <form action="{{ route('service.payment-request.update', ['paymentUuid' => $state_token->uuid ]) }}" method="POST">
                        {{ method_field('PUT') }}
                        {!! csrf_field() !!}
                        <button class="link-button link-button-small" type="submit">
                        <i class="fa fa-money button-icon" aria-hidden="true"></i>Pay <b>{{ $number_to_pay }}</b> Vouchers
                        </button>
                    </form>
                @endif
            @else
                <p class="content-warning center">This payment request is invalid, or has expired.</p>
            @endif
        </div>
    </div>
@endsection
