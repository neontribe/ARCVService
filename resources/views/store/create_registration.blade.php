@extends('store.layouts.service_master')

@section('content')
@if ($programme !==0)
    @include('store.partials.navbar', ['headerTitle' => 'New household sign up'])
@else
    @include('store.partials.navbar', ['headerTitle' => 'New family sign up'])
@endif

    @if ($programme !== 0)
        <div class="content">
            <form action="{{ URL::route("store.registration.store") }}" method="post" class="full-height">
            {!! csrf_field() !!}
                @include('store.partials.voucher_collectorsSP')
                @include('store.partials.householdSP')
                @include('store.partials.other_infoSP')
            </form>
        </div>
        @else
        <div class="content">
            <form action="{{ URL::route("store.registration.store") }}" method="post" class="full-height">
            {!! csrf_field() !!}
                @include('store.partials.voucher_collectors')
                @include('store.partials.family')
                @include('store.partials.other_info')
            </form>
        </div>
        @endif

<script src="{{ asset('js/create_registration.js') }}"></script>

@endsection