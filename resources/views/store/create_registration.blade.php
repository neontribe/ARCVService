@extends('store.layouts.service_master')

@section('content')

    @if ($programme !==0)
        @include('store.partials.navbar', ['headerTitle' => 'New household sign up'])
        @section('title', 'Add a new Household')
    @else
        @include('store.partials.navbar', ['headerTitle' => 'New family sign up'])
        @section('title', 'Add a new Family')
    @endif

    @if ($programme !== 0)
        <div class="content">
            <form action="{{ URL::route("store.registration.store") }}" method="post" class="full-height">
                {!! csrf_field() !!}
                @include('store.registrations.voucher_collectorsSP')
                @include('store.registrations.householdSP')
                @include('store.registrations.other_infoSP')
            </form>
        </div>
    @else
        <div class="content">
            <form action="{{ URL::route("store.registration.store") }}" method="post" class="full-height">
                {!! csrf_field() !!}
                @include('store.registrations.voucher_collectors')
                @include('store.registrations.family')
                @include('store.registrations.other_info')
            </form>
        </div>
    @endif

    <script src="{{ asset('store/js/create_registration.js') }}"></script>
    @stack("bottom")
@endsection

