@extends('store.layouts.service_master')

@section('title', 'Dashboard')

@section('content')

    @include('store.partials.navbar', ['headerTitle' => 'Main menu'])

    <div class="content navigation">
        <ul>
            <a href="{{ URL::route('store.registration.create') }}">
                <li>
                    <img src="{{ asset('store/assets/add-pregnancy-light.svg') }}" id="add-family">
                    Add a new {{ App\Family::getAlias($programme) }}
                </li>
            </a>
            <a href="{{ URL::route('store.registration.index') }}">
                <li>
                    <img src="{{ asset('store/assets/search-light.svg') }}" id="search">
                    Search for a  {{ App\Family::getAlias($programme) }}
                </li>
            </a>
            @if ($user->role !== 'foodmatters_user')
                <a href="{{ $print_route }}" target="_blank" rel="noopener noreferrer">
                    <li>
                        <img src="{{ asset('store/assets/print-light.svg') }}" id="print-registrations">
                        {{ $print_button_text }}
                    </li>
                </a>
                @can('viewRelevantCentre', $user->centre)
                    @can('download', App\CentreUser::class)
                        <a href="{{ URL::route('store.centre.registrations.summary', ['centre' => $centre_id ]) }}" target="_blank" rel="noopener noreferrer">
                            <li>
                                <img src="{{ asset('store/assets/export-light.svg') }}" id="export-centre-registrations">
                                Export {{ $centre_name }} Registrations
                            </li>
                        </a>
                    @endcan
                @endcan
            @endif
            @can('export', App\CentreUser::class)
                <a href="{{ URL::route('store.centres.registrations.summary') }}" target="_blank" rel="noopener noreferrer">
                    <li>
                        <img src="{{ asset('store/assets/export-light.svg') }}" id="export-all-registrations">
                        Export Standard Registrations
                    </li>
                </a>
                <a href="{{ route('store.centres.registrations.summary', ['programme' => 1]) }}" target="_blank" rel="noopener noreferrer">
                    <li>
                        <img src="{{ asset('store/assets/export-light.svg') }}" id="export-all-sp-registrations">
                        Export Social Prescription Registrations
                    </li>
                </a>
                <a href="{{ URL::route('store.vouchers.mvl.export') }}" target="_blank" rel="noopener noreferrer">
                    <li>
                        <img src="{{ asset('store/assets/export-light.svg') }}" id="export-mvl">
                        Export Voucher Log
                    </li>
                </a>
                <a href="{{ URL::route('store.vouchers.mvl.historical') }}" target="_blank" rel="noopener noreferrer">
                    <li>
                        <img src="{{ asset('store/assets/history-light.svg') }}" id="export-mvl">
                        View Historic Voucher Logs
                    </li>
                </a>
            @endcan
        </ul>
    </div>
@endsection
