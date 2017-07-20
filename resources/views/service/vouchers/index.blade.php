@extends('service.layouts.app')

@section('content')
<div id="container">

    @include('service.includes.sidebar')

    <div id="main-content">

      <h1>View live vouchers</h1>

      <ul>
      @foreach ($vouchers as $voucher)

      <li>{{ $voucher }}</li>

      @endforeach
      </ul>

    </div>

</div>

@endsection
