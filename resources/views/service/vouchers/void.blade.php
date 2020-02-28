@extends('service.layouts.app')
@section('content')

<div id="container">
    @include('service.includes.sidebar')
    <div id="main-content">

        <h1>Void a batch of voucher codes</h1>

        <p>Use the form below to <strong>void</strong> a batch of vouchers. Select an area, and then enter the starting and ending voucher code numbers.</p>

        <form role="form" method="POST" action="{{ route('admin.vouchers.updatebatch') }}" class="styled-form">
            {!! method_field("PATCH") !!}
            {!! csrf_field() !!}

            <div class="horizontal-container">
                <div class="select">
                    <label for="sponsor_id">Area</label>
                    <select name="sponsor_id" id="sponsor_id" class="{{ $errors->has('sponsor_id') ? 'has-error' : '' }}" required>
                        <option value="">Please select an area</option>
                        @foreach ($sponsors as $sponsor)
                            <option value="{{ $sponsor->id }}" @if( old('sponsor_id') == $sponsor->id ) SELECTED @endif >{{ $sponsor->name }}</option>
                        @endforeach
                    </select>
                    @if ($errors->has('sponsor_id'))
                        <label for="sponsor_id" class="alert-danger">{{ $errors->first('sponsor_id') }}</label>
                    @endif
                </div>

                <div>
                    <label for="start" class="required">Starting voucher code</label>
                    <input type="text" id="start" name="start" value="{{ old('start') }}" class="{{ $errors->has('start') ? 'error' : '' }}" required>
                    @if ($errors->has('start')) <label for="start" class="alert-danger">{{ $errors->first('start') }}</label> @endif
                    @if ($errors->has('start-serial')) <label for="start" class="alert-danger">{{ $errors->first('start-serial') }}</label> @endif
                </div>

                <div>
                    <label for="end">Ending voucher code</label>
                    <input type="text" id="end" name="end" value="{{ old('end') }}" class="{{ $errors->has('end') ? 'error' : '' }}" required>
                    @if ($errors->has('end')) <label for="end" class="alert-danger">{{ $errors->first('end') }}</label> @endif
                    @if ($errors->has('end-serial')) <label for="start" class="alert-danger">{{ $errors->first('end-serial') }}</label> @endif
                </div>
            </div>
            <div class="container">
                <div classs="row">
                    <div class="col-sm-5">
                        <button type="submit" name="transition" value="expire">Expire vouchers</button>
                    </div>
                    <div class="col-sm-7">
                        <button type="submit" name="transition" value="lose">Void vouchers</button>
                    </div>
                </div>
            </div>
        </form>

    </div>
</div>

@endsection
