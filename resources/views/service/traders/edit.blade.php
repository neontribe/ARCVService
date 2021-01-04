@extends('service.layouts.app')
@section('content')

    <div id="container">
        @include('service.includes.sidebar')
        <div id="main-content">

            <h1>Edit Trader</h1>
            <p>Edit <em>{{ $trader->name }}</em> and its users or market</p>

            @if(isset($trader->disabled_at))
                <div class="alert alert-warning">
                    This trader is currently disabled.
                </div>
            @endif

            <form role="form"
                  class="styled-form"
                  method="POST"
                  action="{{ route('admin.traders.update', ["id" => $trader->id]) }}"
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
                        <label for="disable-toggle"
                        >Disabled</label>
                        <input type="checkbox"
                               id="disable-toggle"
                               name="disabled"
                               class="checkbox {{ $errors->has('disabled') ? 'error' : '' }}"
                               @if(isset($trader->disabled_at))
                                   CHECKED
                               @endif
                        >
                        @include('service.partials.validationMessages', ['inputName' => 'disabled'])
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