@extends('store.layouts.service_master')

@section('title', 'Dashboard')

@section('content')

  @include('store.partials.navbar', ['headerTitle' => 'Main menu'])

    <div class="content navigation">
        <ul>
            <a href="{{ URL::route('store.registration.create') }}">
                <li>
                    <img src="{{ asset('store/assets/add-pregnancy-light.svg') }}" name="add-family">
                    Add a new family
                </li>
            </a>
            <a href="{{ URL::route('store.registration.index') }}">
                <li>
                    <img src="{{ asset('store/assets/search-light.svg') }}" name="search">
                    Search for a family
                </li>
            </a>
            <a href="{{ $print_route }}" target="_blank" >
                <li>
                    <img src="{{ asset('store/assets/print-light.svg') }}" name="print-registrations">
                    {{ $print_button_text }}
                </li>
            </a>
            @can( 'export', App\Registration::class )
            <a href="{{ URL::route('store.centres.registrations.summary') }}">
                <li>
                    <img src="{{ asset('store/assets/export-light.svg') }}" name="export-registrations">
                    Export Registrations
                </li>
            </a>
            <a href="{{ URL::route('store.vouchers.mvl.export') }}">
                <li>
                    <img src="{{ asset('store/assets/export-light.svg') }}" name="export-registrations">
                    Export Voucher Log
                </li>
            </a>
            @endcan
        </ul>
    </div>
@endsection
