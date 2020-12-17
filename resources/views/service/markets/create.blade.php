@extends('service.layouts.app')
@section('content')

<div id="container">
    @include('service.includes.sidebar')
    <div id="main-content">

        <h1>Add Market</h1>

        <p>Add a Market to an Area</p>

        <form role="form" class="styled-form" method="POST" action="{{ route('admin.markets.store') }}">
            @csrf
            <div class="horizontal-container">
                <div class="select">
                    <label for="sponsor">Area</label>
                    <select name="sponsor" id="sponsor" class="{{ $errors->has('sponsor') ? 'error' : '' }}" required>
                        <option value="">Choose one</option>
                        @foreach ($sponsors as $sponsor)
                            <option value="{{ $sponsor->id }}">{{ $sponsor->name }}</option>
                        @endforeach
                    </select>
                    @include('service.partials.validationMessages', array('inputName' => 'sponsor'))
                </div>
                <div>
                    <label for="name" class="required">Name</label>
                    <input type="text" id="name" name="name" class="{{ $errors->has('name') ? 'error' : '' }}" required>
                    @include('service.partials.validationMessages', array('inputName' => 'name'))
                </div>
            </div>
            <div class="horizontal-container">
                <div>
                    <label for="payment_message" class="required">Voucher return message</label>
                    <textarea id="payment_message" name="payment_message" rows=5 maxlength=160 class="{{ $errors->has('payment_message') ? 'error' : '' }}">Please post your vouchers to the project office marked with your stall name and today's date.</textarea>
                </div>
            </div>
            <button type="submit" id="updateMarket">Save Market</button>
        </form>
    </div>
</div>

@endsection