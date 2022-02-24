@extends('service.layouts.app')

@section('content')
<div id="container">

    @include('service.includes.sidebar')

    <div id="main-content">
        <h1>View live vouchers</h1>
        <div>
        </div>
        <table id='liveVoucherTable' class="table table-striped">
            <thead>
                <tr>
                    <th>Voucher ID</th>
                    <th>Trader ID</th>
                    <th>Voucher code</th>
                    <th>Status</th>
                    <th>Area ID</th>
                    <th>Created at</th>
                    <th>Updated at</th>
                    <th>Deleted at</th>
                    <th>View</th>
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
                          <td>
                              <a href="{{ route('service.vouchers.viewone', $voucher->id) }}" class="link">
                                  <div class="link-button link-button-small">
                                      Edit
                                  </div>
                              </a>
                          </td>
                      </tr>
                  @endforeach
            </tbody>
        </table>
        <div>
        </div>
    </div>

</div>
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
<script>
  $(document).ready( function () {
    $('#liveVoucherTable').DataTable();
  } );
</script>
@endsection
