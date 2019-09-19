@extends('service.layouts.app')
@section('content')

<div id="container">
    @include('service.includes.sidebar')
    <div id="main-content">

        <h1>Send vouchers</h1>
        @if (Session::get('error_message'))
            <div class="alert alert-danger">
                {{ Session::get('error_message') }}
            </div>
        @endif
        <p>Use the form below to mark a batch of vouchers as being sent. Add the centre they're being sent to, start and end voucher codes of the batch and the date they're being sent.</p>

        <form role="form" class="styled-form" method="POST" action="{{ route('admin.deliveries.store') }}">
            {!! csrf_field() !!}
            <div class="horizontal-container">
                <div class="select">
                    <label for="centre" class="required">Centre</label>
                    <select name="centre" id="centre" class="{{ $errors->has('centre') ? 'error' : '' }}" required>
                        <option value="">Choose one</option>
                        @foreach ($sponsors as $sponsor)
                            <optgroup label="{{$sponsor->name}}">
                            @foreach ($sponsor->centres as $centre)
                                <option value="{{ $centre->id }}" @if(old('centre') == $centre->id) SELECTED @endif >{{ $centre->name }}</option>
                            @endforeach
                        @endforeach
                    </select>
                    @include('service.partials.validationMessages', array('inputName' => 'centre'))
                </div>
                <div>
                    <label for="voucher-start" class="required">Start Voucher</label>
                    <input type="text" id="voucher-start" name="voucher-start" value="{{ old('voucher-start') }}" class="{{ $errors->has('voucher-start') ? 'error' : '' }} uppercase" required >
                    @include('service.partials.validationMessages', array('inputName' => 'voucher-start'))
                </div>
                <div>
                    <label for="voucher-end" class="required">End Voucher</label>
                    <input type="text" id="voucher-end" name="voucher-end" value="{{ old('voucher-end') }}" class="{{ $errors->has('voucher-end') ? 'error' : '' }} uppercase" required >
                    @include('service.partials.validationMessages', array('inputName' => 'voucher-end'))
                </div>
                <div>
                    <label for="date-sent" class="required">Date Sent</label>
                    <input type="date" id="date-sent" name="date-sent" value={{ old('date-sent') ?? date("Y-m-d") }} class="{{ $errors->has('date-sent') ? 'error' : '' }}" required >
                    @include('service.partials.validationMessages', array('inputName' => 'date-sent'))
                </div>
            </div>
            <button type="submit" id="createDelivery">Create Delivery</button>
        </form>
    </div>
</div>

@endsection