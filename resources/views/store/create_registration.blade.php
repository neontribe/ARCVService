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
                @include('store.partials.first_columnSP')
                @include('store.partials.middle_columnSP')
                @include('store.partials.last_columnSP')
            </form>
        </div>
        @else
        <div class="content">
            <form action="{{ URL::route("store.registration.store") }}" method="post" class="full-height">
            {!! csrf_field() !!}
                @include('store.partials.first_column')
                @include('store.partials.middle_column')
                @include('store.partials.last_column')
            </form>
        </div>
        @endif
        
<script src="{{ asset('js/create_registration.js') }}"></script>

@endsection