@extends('service.layouts.app')
@section('content')

    <div id="container">
        @include('service.includes.sidebar')
        <div id="main-content">

            <h1>Edit Trader</h1>

            <p>Edit a trader, and maybe it's users</p>

            <form role="form"
                  class="styled-form"
                  method="POST"
                  action="{{ route('admin.traders.store') }}"
            >
                @csrf
                @method('put')
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
                                        <option value="{{ $market->id }}"
                                                @if($market->id === $trader->market_id)
                                                SELECTED
                                            @endif
                                        >{{ $market->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @include('service.partials.validationMessages', ['inputName' => 'market'])
                    </div>
                    <div>
                        <label for="name"
                               class="required"
                        >Trader Stall Name</label>
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ $trader->name }}"
                               class="{{ $errors->has('name') ? 'error' : '' }}"
                               maxlength="160"
                               required
                        >
                        @include('service.partials.validationMessages', ['inputName' => 'name'])
                    </div>
                    <div>
                        <label for="name" class="required">Trader Stall Location</label>
                        <input type="text"
                               id="location"
                               name="location"
                               value="{{ $trader->location }}"
                               class="{{ $errors->has('trader') ? 'error' : '' }}"
                               maxlength="160"
                               required
                        >
                        @include('service.partials.validationMessages', ['inputName' => 'location'])
                    </div>
                    <hr>
                </div>
                <hr>
                @include('service.partials.addTraderUser', ['users' => $trader->users ])
                <button type="submit" id="updateMarket">Update All</button>
            </form>
        </div>
    </div>

@endsection