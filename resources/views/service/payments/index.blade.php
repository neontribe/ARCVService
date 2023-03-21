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
                <tr>
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
                    @foreach ($payments as $payment)
                    <tr data-toggle="collapse" data-target="#varea{{$payment->vid}}" class="accordion-toggle">
                        <td><span class="glyphicon glyphicon-th-list"></span>   {{ $payment->tname }}</td>
                        <td>{{ $payment->mname }}</td>
                        <td>{{ $payment->msponname }}</td>
                        <td>{{ $payment->uname }}</td>
                        <td>{{ $payment->vsponname }}</td>
                        <td>
                            <a href="{{ route('admin.payment-request.show', ['id' => $payment->vid ]) }}" class="link">
                                <div class="link-button link-button-small">
                                    <i class="fa fa-money button-icon" aria-hidden="true"></i>Pay Request
                                </div>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="accordian-body collapse" id=varea{{$payment->vid}}>
                                <table class="table table-striped">
                                    <tbody>
                                        <tr>
                                            <td>{{ $payment->total }}</td>
                                            <td>{{ $payment->byarea }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

        </div>

    </div>

@endsection