@extends('service.layouts.app')
@section('content')

    <div id="container">
        @include('service.includes.sidebar')
        <div id="main-content">

            <h1>Edit Market</h1>

            <p>Edit a Market</p>

            <form role="form" class="styled-form" method="POST" action="{{ route('admin.markets.update') }}">
                @method('put')
                @csrf
                <div class="horizontal-container">
                    <div class="select">
                        <label for="sponsor">Area</label>
                        <select name="sponsor" id="sponsor" class="{{ $errors->has('sponsor') ? 'error' : '' }}" required>
                            <option value="">Choose one</option>
                            @foreach ($sponsors as $sponsor)
                                <option value="{{ $sponsor->id }}"
                                    @if($sponsor->selected === true)
                                        selected
                                    @endif
                                >{{ $sponsor->name }}</option>
                            @endforeach
                        </select>
                        @include('service.partials.validationMessages', array('inputName' => 'sponsor'))
                    </div>
                    <div>
                        <label for="name" class="required">Name</label>
                        <input type="text" id="name" value="{{ $market->name }}" name="name"
                               class="{{ $errors->has('name') ? 'error' : '' }}" required>
                        @include('service.partials.validationMessages', array('inputName' => 'name'))
                    </div>
                </div>
                <div class="horizontal-container">
                    <label for="payment_message">Voucher return message</label>
                    <textarea id="payment_message" name="payment_message">{{ $market->payment_message }} </textarea>
                </div>
                <button type="submit" id="createMarket">Save Market</button>
            </form>
        </div>
    </div>

@endsection