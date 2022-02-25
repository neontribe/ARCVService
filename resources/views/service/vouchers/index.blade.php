@extends('service.layouts.app')

@section('content')
<div id="container">

    @include('service.includes.sidebar')

    <div id="main-content">
        <h1>View live vouchers</h1>
        <div class="content search">
            <div class="control-container">
              <form action="{{ URL::route('admin.vouchers.search') }}" method="GET" id="searchform">
                  {!! csrf_field() !!}
                  {{-- Voucher search --}}
                  <div class="search-control">
                      <label for="voucher_code">Search by voucher code</label>
                      <div class="search-actions">
                          <input type="text" name="voucher_code" id="voucher_code" autocomplete="off" autocorrect="off"
                              spellcheck="false" placeholder="Enter voucher code" aria-label="Voucher Code">
                          <button name="search" aria-label="Search" class="link-button link-button-small">Search</button>
                          <button class="link-button link-button-small"><a style="color:white;text-decoration:none;" href="{{ route('admin.vouchers.index') }}">Reset</a></button>
                      </div>
                  </div>
              </form>
            </div>
          </div>
            @if($vouchers instanceof \Illuminate\Pagination\LengthAwarePaginator)
              <div>
                <p>{{ $vouchers->links() }}</p>
                <p>Total : {{ $vouchers->total() }}</p>
              </div>
            @endif
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
        @if($vouchers instanceof \Illuminate\Pagination\LengthAwarePaginator)
          <div>
            <p>{{ $vouchers->links() }}</p>
            <p>Total : {{ $vouchers->total() }}</p>
          </div>
        @endif
        <div>
        </div>
    </div>

</div>
@endsection
