@extends('service.layouts.app')

@section('content')

  <div id="main-content">
      <h1>Voucher Code: {{ $voucher->code }}</h1>
      <button class="link-button link-button-small"><a style="color:white;text-decoration:none;" href="{{ route('admin.vouchers.index') }}">Back</a></button>
      <table class="table table-striped">
          <thead>
              <tr>
                  <th>Transition</th>
                  <th>From</th>
                  <th>To</th>
                  <th>Updated_at</th>
              </tr>
            </thead>
            <tbody>
                @foreach ($voucher->history as $history)
                    <tr>
                        <td>{{ $history->transition }}</td>
                        <td>{{ $history->from }}</td>
                        <td>{{ $history->to }}</td>
                        <td>{{ $history->updated_at }}</td>
                    </tr>
                @endforeach
          </tbody>
      </table>
  </div>
  <form role="form" class="styled-form" method="POST" action="{{ route('admin.vouchers.retirebatch') }}">
      {!! method_field("PATCH") !!}
      {!! csrf_field() !!}
          <input type="hidden"
                 id="voucher-start"
                 name="voucher-start"
                 value="{{ $voucher->code }}">
          <input type="hidden"
                 id="voucher-end"
                 name="voucher-end"
                 value="{{ $voucher->code }}">
      <div class="col-sm-6 col-lg-3">
          <button type="submit" name="transition" value="void">Void voucher</button>
      </div>
  </form>
@endsection
