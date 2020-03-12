@extends('service.layouts.app')
@section('content')

    <div id="container">
        @include('service.includes.sidebar')
        <div id="main-content">

            <h1>Void vouchers</h1>
            @if (Session::get('error_message'))
                <div class="alert alert-danger">
                    {{ Session::get('error_message') }}
                </div>
            @endif
            <p>Use the form below to mark a batch of vouchers as "Void" or "Expired". Add start and end voucher codes of the batch.</p>

            <form role="form" class="styled-form" method="POST" action="{{ route('admin.vouchers.updatebatch') }}">
                {!! method_field("PATCH") !!}
                {!! csrf_field() !!}
                <div class="container">
                    <div class="row">
                        <div class="col-sm-6 col-lg-3">
                            <label for="voucher-start" class="required">Start Voucher</label>
                            <input type="text"
                                   id="voucher-start"
                                   name="voucher-start"
                                   value="{{ old('voucher-start') }}"
                                   class="{{ $errors->has('voucher-start') ? 'error' : '' }} uppercase"
                                   required >
                            @include('service.partials.validationMessages', array('inputName' => 'voucher-start'))
                        </div>
                        <div class="col-sm-6 col-lg-9">
                            <label for="voucher-end" class="required">End Voucher</label>
                            <input type="text"
                                   id="voucher-end"
                                   name="voucher-end"
                                   value="{{ old('voucher-end') }}"
                                   class="{{ $errors->has('voucher-end') ? 'error' : '' }} uppercase"
                                   required >
                            @include('service.partials.validationMessages', array('inputName' => 'voucher-end'))
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 col-lg-3">
                            <button type="submit" name="transition" value="expire">Expire vouchers</button>
                        </div>
                        <div class="col-sm-6 col-lg-9">
                            <button type="submit" name="transition" value="void">Void vouchers</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection