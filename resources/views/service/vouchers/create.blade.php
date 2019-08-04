@extends('service.layouts.app')
@section('content')

<div id="container">
    @include('service.includes.sidebar')
    <div id="main-content">

        <h1>Add a batch of voucher codes</h1>

        <p>Use the form below to add a new batch of vouchers. Select an area, and then enter the starting and ending voucher code numbers.</p>

        <form role="form" method="POST" action="{{ route('admin.vouchers.storebatch') }}" class="styled-form add-vouchers">
            {!! csrf_field() !!}

            <div class="select">
                <label for="sponsor_id">Area</label>
                <select name="sponsor_id" id="sponsor_id" class="{{ $errors->has('sponsor_id') ? 'has-error' : '' }}" required>
                    <option value="">Please select an area</option>
                    @foreach ($sponsors as $sponsor)
                    <option value="{{ $sponsor->id }}">{{ $sponsor->name }}</option>
                    @endforeach
                </select>
            </div>

            @if ($errors->has('sponsor_id'))
            <p class="error">{{ $errors->first('sponsor_id') }}</p>
            @endif

            <div>
                <label for="start" class="required">Starting voucher code</label>
                <input type="text" id="start" name="start" class="{{ $errors->has('start') ? 'error' : '' }}" required>
            </div>

            @if ($errors->has('start'))
            <p class="error">{!! $errors->first('start') !!}</p>
            @endif

            <div>
                <label for="end">Ending voucher code</label>
                <input type="text" id="end" name="end" class="{{ $errors->has('end') ? 'error' : '' }}" required>
            </div>

            @if ($errors->has('end'))
            <p class="error">{{ $errors->first('end') }}</p>
            @endif

            <button type="submit" id="createVouchers">Create vouchers</button>

        </form>

    </div>
</div>

@endsection
