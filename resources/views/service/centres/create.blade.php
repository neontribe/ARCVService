@extends('service.layouts.app')
@section('content')

<div id="container">
    @include('service.includes.sidebar')
    <div id="main-content">

        <h1>Add a sponsor</h1>

        <p>Use the form below to add a new sponsor. Add their name and voucher prefix.</p>

        <!-- ADD NEW POST ROUTE HERE eg. method="POST" action="{{ route('admin.vouchers.storebatch') }}" -->
        <form role="form" class="styled-form">
            {!! csrf_field() !!}
            <div class="horizontal-container">
                <div>
                    <label for="name" class="required">Name</label>
                    <input type="text" id="name" name="name" class="{{ $errors->has('name') ? 'error' : '' }}" required>
                </div>
                <div>
                    <label for="voucher_prefix" class="required">Voucher Prefix</label>
                    <input type="text" id="voucher_prefix" name="voucher_prefix" class="{{ $errors->has('voucher_prefix') ? 'error ' : '' }}uppercase" required>
                </div>
                <div class="select">
                    <label for="sponsor">Sponsor</label>
                    <select name="sponsor" id="sponsor" class="{{ $errors->has('sponsor') ? 'error' : '' }}" required>
                        <option value="">Choose one</option>
                        @foreach ($sponsors as $sponsor)
                            <option value="{{ $sponsor->id }}">{{ $sponsor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="select">
                    <label for="form">Form</label>
                    <select name="form" id="form" class="{{ $errors->has('form') ? 'error' : '' }}" required>
                        <option value="">Choose one</option>
                        @foreach (config('arc.print_preferences') as $preference)
                            <option value="{{ $preference }}">{{ $preference }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" id="createSponsor">Save</button>
        </form>
    </div>
</div>

@endsection