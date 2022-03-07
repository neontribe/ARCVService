@extends('service.layouts.app')
@section('content')

<div id="container">
    @include('service.includes.sidebar')
    <div id="main-content">

        <h1>Add an area</h1>

        <p>Use the form below to add a new area. Add their name and voucher prefix.</p>

        <form role="form" class="styled-form" method="POST" action="{{ route('admin.sponsors.store') }}">
            {!! csrf_field() !!}
            <div class="horizontal-container">
                <div>
                    <label for="name" class="required">Name</label>
                    <input type="text" id="name" name="name" class="{{ $errors->has('name') ? 'error' : '' }}" required>
                    @include('service.partials.validationMessages', array('inputName' => 'name'))
                </div>
                <div>
                    <label for="voucher_prefix" class="required">Voucher Prefix</label>
                    <input type="text" id="voucher_prefix" name="voucher_prefix" class="{{ $errors->has('voucher_prefix') ? 'error ' : '' }} uppercase" required>
                    @include('service.partials.validationMessages', array('inputName' => 'voucher_prefix'))
                </div>
                {{-- <div class="select">
                    <label for="is_scotland">Centre is in Scotland?</label>
                    <input type="checkbox" class="scotlandCheckbox" id="is_scotland" name="is_scotland" @if( old('is_scotland') ) checked @endif/>
                </div> --}}
            </div>
            <button type="submit" id="createSponsor">Save</button>
        </form>
    </div>
</div>

@endsection
