@extends('service.layouts.app')
@section('content')

<div id="container">
    @include('service.includes.sidebar')
    <div id="main-content">
    <h1>Add a batch of vouchers</h1>

     <form role="form" method="POST" action="{{ route('vouchers.storebatch') }}">
            {!! csrf_field() !!}
            <p>
                <label for="sponsor_id" class="required">Sponsor</label>
                <select name="sponsor_id" id="sponsor_id" class="{{ $errors->has('sponsor_id') ? 'has-error' : '' }}">
                    <option value="">Please Select a Sponsor</option>
                    @foreach ($sponsors as $sponsor)
                    <option value="{{ $sponsor->id }}">{{ $sponsor->name }}</option>
                    @endforeach
                </select>
            </p>
            @if ($errors->has('sponsor_id'))
            <p class="error">{{ $errors->first('sponsor_id') }}</p>
            @endif
            <p>
                <label for="start" class="required">Voucher Batch Start</label>
                <input type="text" id="start" name="start" class="{{ $errors->has('start') ? 'error' : '' }}" required>
            </p>
            @if ($errors->has('start'))
            <p class="error">{!! $errors->first('start') !!}</p>
            @endif
            <p>
                <label for="current_password"></label>
                <input type="text" id="end" name="end" class="{{ $errors->has('end') ? 'error' : '' }}">
            </p>
            @if ($errors->has('end'))
            <p class="error">{{ $errors->first('end') }}</p>
            @endif
            <p>
                <button type="submit">Create Vouchers</button>
            </p>
        </form>
    </div>
</div>
@endsection
