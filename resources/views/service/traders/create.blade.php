@extends('service.layouts.app')
@section('content')

    <div id="container">
        @include('service.includes.sidebar')
        <div id="main-content">

            <h1>Add Trader</h1>

            <p>Add a trader, maybe with users, to a market</p>

            <form role="form"
                  class="styled-form"
                  method="POST"
                  action="{{ route('admin.traders.store') }}"
            >
                @csrf
                <div class="horizontal-container">
                    <div class="select">
                        <label for="market">Market</label>
                        <select name="market"
                                id="market"
                                class="{{ $errors->has('market') ? 'error' : '' }}"
                                required
                        >
                            <option value="">Choose one</option>
                            @foreach ($marketsBySponsor as $sponsor)
                                <optgroup label="{{ $sponsor->name }}">
                                    @foreach ($sponsor->markets as $market)
                                        <option value="{{ $market->id }}">{{ $market->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @include('service.partials.validationMessages', array('inputName' => 'market'))
                    </div>
                    <div>
                        <label for="name"
                               class="required"
                        >Trader Stall Name</label>
                        <input type="text"
                               id="name"
                               name="name"
                               class="{{ $errors->has('name') ? 'error' : '' }}"
                               maxlength="160"
                               required
                        >
                        @include('service.partials.validationMessages', array('inputName' => 'name'))
                    </div>
                    <div>
                        <label for="name" class="required">Trader Stall Location</label>
                        <input type="text"
                               id="location"
                               name="location"
                               class="{{ $errors->has('name') ? 'error' : '' }}"
                               maxlength="160"
                               required
                        >
                        @include('service.partials.validationMessages', ['inputName' => 'location'])
                    </div>
                </div>
                <hr>
                @include('service.partials.addTraderUser')
                <button type="submit" id="updateMarket">Save Trader</button>
            </form>
        </div>
    </div>

@endsection