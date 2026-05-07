@extends('service.layouts.app')
@section('content')

    <div id="container">
        @include('service.includes.sidebar')
        <div id="main-content">

            <h1>Edit Children's Centre</h1>

            <p>Use the form below to edit this children's centre. Update their name, RVID prefix, area, form style and collecting state</p>

            <form class="styled-form"
                  method="POST"
                  action="{{ route('admin.centres.update', $centre) }}"
            >
                @csrf
                @method('PUT')
                <div class="horizontal-container">
                    <div>
                        <label for="name" class="required">Name</label>
                        <input type="text"
                               id="name"
                               name="name"
                               class="@error('name') error @enderror"
                               value="{{ old('name', $centre->name) }}"
                               required
                        >
                        @include('service.partials.validationMessages', ['inputName' => 'name'])
                    </div>
                    <div>
                        <label for="prefix" class="required">RVID prefix</label>
                        <input type="text"
                               id="prefix"
                               name="prefix"
                               class="@error('prefix') error @enderror uppercase"
                               value="{{ old('prefix', $centre->prefix) }}"
                               required
                        >
                        @include('service.partials.validationMessages', ['inputName' => 'prefix'])
                    </div>
                    <div class="select">
                        <label for="sponsor_id">Area</label>
                        <select name="sponsor_id"
                                id="sponsor_id"
                                class="@error('sponsor_id') error @enderror"
                                required
                        >
                            <option value="">Choose one</option>
                            @foreach ($sponsors as $sponsor)
                                <option value="{{ $sponsor->id }}"
                                    @selected(old('sponsor_id', $centre->sponsor_id) === $sponsor->id)
                                >{{ $sponsor->name }}</option>
                            @endforeach
                        </select>
                        @include('service.partials.validationMessages', ['inputName' => 'sponsor_id'])
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
                                    @selected(old('print_pref', $centre->print_pref) === $pref)
                                >{{ ucwords($pref) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox"
                           id="can_collect"
                           name="can_collect"
                           class="styled-checkbox @error('can_collect') error @enderror"
                        @checked(old('can_collect', $centre->can_collect))
                    >
                    <label for="can_collect">Can Redeem Vouchers</label>
                    @include('service.partials.validationMessages', ['inputName' => 'can_collect'])
                </div>
                <button type="submit" id="updateCentre">Save</button>
            </form>
        </div>
    </div>

@endsection
