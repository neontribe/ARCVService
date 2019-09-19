@extends('service.layouts.app')
@section('content')

<div id="container">
    @include('service.includes.sidebar')
    <div id="main-content">

        <h1>Add a Children's Centre</h1>

        <p>Use the form below to add a new children's centre. Add their name, RVID prefix, area and form.</p>

        <form role="form" class="styled-form" method="POST" action="{{ route('admin.centres.store') }}">
            {!! csrf_field() !!}
            <div class="horizontal-container">
                <div>
                    <label for="name" class="required">Name</label>
                    <input type="text" id="name" name="name" class="{{ $errors->has('name') ? 'error' : '' }}" required>
                    @include('service.partials.validationMessages', array('inputName' => 'name'))
                </div>
                <div>
                    <label for="rvid_prefix" class="required">RVID prefix</label>
                    <input type="text" id="rvid_prefix" name="rvid_prefix" class="{{ $errors->has('rvid_prefix') ? 'error ' : '' }} uppercase" required>
                    @include('service.partials.validationMessages', array('inputName' => 'rvid_prefix'))
                </div>
                <div class="select">
                    <label for="sponsor">Area</label>
                    <select name="sponsor" id="sponsor" class="{{ $errors->has('sponsor') ? 'error' : '' }}" required>
                        <option value="">Choose one</option>
                        @foreach ($sponsors as $sponsor)
                            <option value="{{ $sponsor->id }}">{{ $sponsor->name }}</option>
                        @endforeach
                    </select>
                    @include('service.partials.validationMessages', array('inputName' => 'sponsor'))
                </div>
                <div class="select">
                    <label for="print_pref">Printed Form</label>
                    <select name="print_pref" id="print_pref" class="{{ $errors->has('print_pref') ? 'error' : '' }}" required>
                        <option value="" disabled selected>Choose one</option>
                        @foreach (config('arc.print_preferences') as $pref)
                            <option value="{{ $pref }}">{{ ucwords($pref) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" id="createSponsor">Save</button>
        </form>
    </div>
</div>

@endsection