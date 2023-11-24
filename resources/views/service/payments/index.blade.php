@extends('service.layouts.app')

@section('content')
    <div id="container">

        @include('service.includes.sidebar')

        <div id="main-content">
            <h1>Payment Requests</h1>
            @if (Session::get('message'))
                <div class="alert alert-success">
                    {{ Session::get('message') }}
                </div>
            @endif
            <table class="table table-striped">
                <thead>
                <tr><th></th>
                    <th>Name</th>
                    <th>Market</th>
                    <th>Area</th>
                    <th>Requested By</th>
                    <th>Voucher Area</th>
                    <th>Total</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                    @foreach ($pending as $key => $paymentDatum)
                        @if (array_key_exists("traderName", $paymentDatum))
                            <tr class="accordion-toggle">
                                <td><span class="glyphicon glyphicon-th-list" data-toggle="collapse" data-target=".varea{{$key}}"></span></td>
                                <td>{{ $paymentDatum["traderName"]}}</td>
                                <td>{{ $paymentDatum["marketName"]}}</td>
                                <td>{{ $paymentDatum["area"]}}</td>
                                <td>{{ $paymentDatum["requestedBy"]}}</td>
                                <td>All</td>
                                <td>{{ $paymentDatum["vouchersTotal"]}}</td>
                                <td>
                                    <a href="{{ route('admin.payment-request.show', ['paymentUuid' => $key]) }}" class="link">
                                        <div class="link-button">
                                            Pay Request
                                        </div>
                                    </a>
                                </td>
                            </tr>
                            @foreach($paymentDatum["voucherAreas"] as $area=>$value)
                            <tr class="accordian-body collapse varea{{$key}}"><td colspan="5"></td>
                                <td>{{ $area }}</td>
                                <td>{{ $value }}</td>
                                <td></td>
                            </tr>
                            @endforeach
                        @else
                            {{ logger('TRADER DATA MISSING: ' . $key) }}
                        @endif
                    @endforeach

                    @foreach ($reimbursed as $key => $paymentDatum)
                    <tr  class="accordion-toggle">
                        <td><span class="glyphicon glyphicon-th-list" data-toggle="collapse" data-target=".varea{{$key}}"></span></td>
                        <td>{{ $paymentDatum["traderName"]}}</td>
                        <td>{{ $paymentDatum["marketName"]}}</td>
                        <td>{{ $paymentDatum["area"]}}</td>
                        <td>{{ $paymentDatum["requestedBy"]}}</td>
                        <td>All</td>
                        <td>{{ $paymentDatum["vouchersTotal"]}}</td>
                        <td><div class="link-button link-button-small paid">
                                <i class="fa fa-money button-icon" aria-hidden="true"></i>Paid
                            </div>
                        </td>
                    </tr>
                    @foreach($paymentDatum["voucherAreas"] as $area=>$value)
                    <tr class="accordian-body collapse varea{{$key}}"><td colspan="5"></td>
                        <td>{{ $area }}</td>
                        <td>{{ $value }}</td>
                        <td></td>
                    </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection