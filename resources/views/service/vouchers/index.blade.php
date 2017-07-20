@extends('service.layouts.app')

@section('content')
<div id="container">

    @include('service.includes.sidebar')

    <div id="main-content">

        <h1>View live vouchers</h1>

        <table class="table table-striped">
            <thead>
              <tr>
                <th>Voucher ID</th>
                <th>Trader ID</th>
                <th>Voucher code</th>
                <th>Status</th>
                <th>Sponsor ID</th>
                <th>Created at</th>
                <th>Updated at</th>
                <th>Deleted at</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($vouchers as $voucher)
                  <tr>
                    <th scope="row">{{ $voucher->id }}</th>
                    <td>{{ $voucher->trader_id }}</td>
                    <td>{{ $voucher->code }}</td>
                    <td>{{ $voucher->currentstate }}</td>
                    <td>{{ $voucher->sponsor_id }}</td>
                    <td>{{ $voucher->created_at }}</td>
                    <td>{{ $voucher->updated_at }}</td>
                    <td>{{ $voucher->deleted_at }}</td>
                  </tr>
              @endforeach
            </tbody>
        </table>

    </div>

</div>

@endsection
