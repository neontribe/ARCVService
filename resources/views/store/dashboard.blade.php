@extends('store.layouts.service_master')

@section('title', 'Dashboard')

@section('content')

    @include('store.partials.navbar', ['headerTitle' => 'Main menu'])

    <div class="content navigation">
        <ul>
            @include('store.components.link-button', [
                'id' => 'add-family',
                'img' => 'store/assets/add-pregnancy-light.svg',
                'route' => ['store.registration.create'],
                'text' => ['components.link-button.add-entity.text', ['entity' => App\Family::getAlias($programme)]]
            ])

            @include('store.components.link-button', [
                'id' => 'search',
                'img' => 'store/assets/search-light.svg',
                'route' => ['store.registration.index'],
                'text' => ['components.link-button.search-entity.text', ['entity' => App\Family::getAlias($programme)]]
            ])

            @if ($user->role !== 'foodmatters_user')

                @if($pref_collection === true)
                    @include('store.components.link-button', [
                        'id' => 'print-registrations',
                        'img' => 'store/assets/print-light.svg',
                        'route' => ['store.centre.registrations.collection', $centre_id],
                        'text' => ['components.link-button.print-collection-sheet.text']
                    ])
                @elseif($pref_collection === false)
                    @include('store.components.link-button', [
                        'id' => 'print-registrations',
                        'img' => 'store/assets/print-light.svg',
                        'route' => ['store.registrations.print'],
                        'text' => ['components.link-button.print-entity-sheets.text', ['entity' => App\Family::getAlias($programme)]]
                    ])
                @endif

                @can('viewRelevantCentre', $user->centre)
                    @can('download', App\CentreUser::class)
                        @include('store.components.link-button', [
                            'id' => 'export-centre-registrations',
                            'img' => 'store/assets/export-light.svg',
                            'route' => ['store.centre.registrations.summary', ['centre' => $centre_id ]],
                            'text' => ['components.link-button.export-entity-registrations.text', ['entity-name' => $centre->name]]
                        ])
                    @endcan
                @endcan
            @endif
            @can('export', App\CentreUser::class)
                <a href="{{ URL::route('store.centres.registrations.summary') }}" target="_blank"
                   rel="noopener noreferrer">
                    <li>
                        <img src="{{ asset('store/assets/export-light.svg') }}" id="export-all-registrations">
                        Export Registrations
                    </li>
                </a>
                <a href="{{ URL::route('store.vouchers.mvl.export') }}" target="_blank" rel="noopener noreferrer">
                    <li>
                        <img src="{{ asset('store/assets/export-light.svg') }}" id="export-mvl">
                        Export Voucher Log
                    </li>
                </a>
            @endcan

        </ul>
    </div>
@endsection
