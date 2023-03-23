@extends('service.layouts.app')

@section('content')
    <div id="container">

        @include('service.includes.sidebar')

        <div id="main-content">
            <h1>Payment History</h1>
            @if (Session::get('message'))
                <div class="alert alert-success">
                    {{ Session::get('message') }}
                </div>
            @endif
            <table class="table table-striped">
                <thead>
                <tr>
                    <th></th>
                    <th>Request Date</th>
                    <th>Vouchers</th>
                    <th>Total</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($history as $key => $payment)
                    <tr  class="accordion-toggle">
                        <td><span class="glyphicon glyphicon-th-list" data-toggle="collapse" data-target=".payment{{$key}}"></span></td>
                        <td>{{ $payment['pended_on'] }}</td>
                        <td>{{ count($payment["vouchers"])}}</td>
                        <td>£{{ count($payment["vouchers"])}}</td>
                    </tr>
                    <tr class="accordian-body collapse payment{{$key}}">
                        <td></td>
                        <td colspan="3">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Voucher Code</th>
                                        <th>Voucher Added On</th>
                                        <th>Voucher Paid On</th>
                                    </tr>
                                </thead>
                                @foreach($payment["vouchers"] as $line)
                                        <tr>
                                            <td>{{ $line['code'] }}</td>
                                            <td>{{ $line['recorded_on'] }}</td>
                                            <td>{{ $line['reimbursed_on'] }}</td>
                                            <td></td>
                                        </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{$history->links()}}
        </div>
    </div>
@endsection