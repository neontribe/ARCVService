@extends('store.layouts.service_master')

@section('title', 'Check / Update Registration')

@section('content')

@include('store.partials.navbar', ['headerTitle' => 'Check or update'])

<div class="content flex">
    <form action="{{ URL::route("store.registration.rejoin",['registration' => $registration]) }}" method="post" class="full-height">
        {{ method_field('PUT') }}
        {!! csrf_field() !!}
        <input type="hidden" name="registration" value="{{ $registration->id }}">
        <div class="col fit-height">
            <div>
                <img src="{{ asset('store/assets/group-light.svg') }}" alt="logo">
                <h2>Voucher collectors</h2>
            </div>
            <div>
                <label for="pri_carer">Main {{ $programme ? 'participant' : 'carer' }}'s full name</label>
                @if (isset($pri_carer))
                   <p><b> {{ $pri_carer->name }} </b></p>
                @endif
            </div>
        </div>

        <div class="col fit-height">
            <div>
                <img src="{{ asset('store/assets/info-light.svg') }}" alt="logo">
                <h2>This family</h2>
            </div>
            @if (isset($family))
                <div>
                    <div>
                        <p>This {{ $programme ? 'household' : 'family' }} has {{ count($family->children) . " " . str_plural('child', count($family->children)) }} and left the project on {{$family->leaving_on->format('d-m-Y')}}</p>
                    </div>
                </div>
            @endif
            <div>
                <button class="long-button submit" formaction="{{ URL::route('store.registration.rejoin',['registration' => $registration]) }}">Rejoin</button>
            </div>
        </div>
    </form>
</div>

<script src="{{ asset('store/js/edit_registration.js') }}"></script>
@stack("bottom")
@endsection