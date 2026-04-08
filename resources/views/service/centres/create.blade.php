@extends('service.layouts.app')
@section('content')

    <div id="container">
        @include('service.includes.sidebar')
        <div id="main-content">

            <h1>Add a Children's Centre</h1>

            <p>Use the form below to add a new children's centre. Add their name, RVID prefix, area and form.</p>

            <form class="styled-form"
                  method="POST"
                  action="{{ route('admin.centres.store') }}"
            >
                @csrf
                <div class="horizontal-container">
                    <div>
                        <label for="name" class="required">Name</label>
                        <input type="text"
                               id="name"
                               name="name"
                               class="@error('name') error @enderror"
                               value="{{ old('name') }}"
                               required
                        >
                        @include('service.partials.validationMessages', ['inputName' => 'name'])
                    </div>
                    <div>
                        <label for="rvid_prefix" class="required">RVID prefix</label>
                        <input type="text"
                               id="rvid_prefix"
                               name="rvid_prefix"
                               class="@error('rvid_prefix') error @enderror uppercase"
                               value="{{ old('rvid_prefix') }}"
                               required
                        >
                        @include('service.partials.validationMessages', ['inputName' => 'rvid_prefix'])
                    </div>
                    <div class="select">
                        <label for="sponsor">Area</label>
                        <select name="sponsor"
                                id="sponsor"
                                class="@error('sponsor') error @enderror"
                                required
                        >
                            <option value="">Choose one</option>
                            @foreach ($sponsors as $sponsor)
                                <option value="{{ $sponsor->id }}"
                                        @selected(old('sponsor') === $sponsor->id)
                                >{{ $sponsor->name }}</option>
                            @endforeach
                        </select>
                        @include('service.partials.validationMessages', ['inputName' => 'sponsor'])
                    </div>
                    <div class="select">
                        <label for="print_pref">Printed Form</label>
                        <select name="print_pref"
                                id="print_pref"
                                class="@error('print_pref') error @enderror"
                                required
                        >
                            <option value="" disabled>Choose one</option>
                            @foreach (config('arc.print_preferences') as $pref)
                                <option value="{{ $pref }}"
                                        @selected(old('print_pref', 'collection') === 'pref')
                                >{{ ucwords($pref) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox"
                           id="can-collect"
                           name="can-collect"
                           class="styled-checkbox @error('can-collect') error @enderror"
                        @checked(old('can-collect') === true)
                    >
                    <label for="can-collect">Can Collect Vouchers</label>
                    @include('service.partials.validationMessages', ['inputName' => 'can-collect'])
                </div>
                <button type="submit" id="createCentre">Save</button>
            </form>
        </div>
    </div>

@endsection
