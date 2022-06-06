@extends('store.layouts.service_master')

@section('title', 'Check / Update Registration')

@section('content')

@include('store.partials.navbar', ['headerTitle' => 'Check or update'])

@if ($programme !== 0)
    <div class="content flex">
        <form action="{{ URL::route("store.registration.update",['registration' => $registration]) }}" method="post" class="full-height">
            {{ method_field('PUT') }}
            {!! csrf_field() !!}

            @include('store.partials.voucher_collectorsSP')
            @include('store.partials.householdSP')
            @include('store.partials.other_infoSP')
        </form>
    </div>
@else
    <div class="content flex">
        <form action="{{ URL::route("store.registration.update",['registration' => $registration]) }}" method="post" class="full-height">
            {{ method_field('PUT') }}
            {!! csrf_field() !!}
            <input type="hidden" name="registration" value="{{ $registration->id }}">
            @include('store.partials.voucher_collectors')
            @include('store.partials.family')
            @include('store.partials.other_info')
        </form>
    </div>
@endif

<script src="{{ asset('store/js/edit_registration.js') }}"></script>
@endsection

