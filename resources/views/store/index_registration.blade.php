@extends('store.layouts.service_master')

@section('title', 'Check / Update Registration')

@section('content')

@include('store.partials.navbar', ['headerTitle' => 'Search for a family'])
@includeWhen(Session::has('message'), 'store.partials.success')
<div class="content search">
    <div class="control-container">
        <form action="{{ URL::route('store.registration.index') }}" method="GET" id="searchform">
            {!! csrf_field() !!}
            {{-- Name search --}}
            <div class="search-control">
                <label for="family_name">Search by name</label>
                <div class="search-actions">
                    <input type="text" name="family_name" id="family_name" autocomplete="off" autocorrect="off"
                        spellcheck="false" placeholder="Enter family name" aria-label="Family Name">
                </div>
            </div>
            {{-- Centre filter --}}
            <div class="filter-control">
                <label for="centre">Filter by centre</label>
                <select name="centre" id="centre">
                    <option value="">All</option>
                    @foreach (Auth::user()->centres as $centre)
                    <option value="{{ $centre->id }}" {{ Request::get("centre")==($centre->id) ? 'selected' : '' }}>
                        {{ $centre->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            {{-- Families left checkbox --}}
            <div class="checkbox-control relative">
                <input type="checkbox" class="styled-checkbox no-margin" id="families_left" name="families_left" {{
                    Request::get("families_left") ? 'checked' : '' }} />
                <label for="families_left">Show families who have left</label>
            </div>
            <button name="search" aria-label="Search" class="search-button">
                <img src="{{ asset('store/assets/search-light.svg') }}" alt="search">
            </button>
        </form>
    </div>
    <div>
        <table>
            <thead>
                <tr>
                    <td>Name<span class="sort-link-container">@include('store.partials.sortableChevron', ['route' =>
                            'store.registration.index', 'orderBy' => 'name', 'direction' => request('direction')
                            ])</span></td>
                    <td class="center">Voucher Entitlement</td>
                    <td class="center">RV-ID</td>
                    <td></td>
                </tr>
            </thead>
            <tbody>
                @foreach ($registrations as $registration)
                @if ($registration->family)
                <tr class="{{ $registration->family->leaving_on ? 'inactive' : 'active' }}">
                    <td class="pri_carer">
                        <div>{{ $registration->family->carers->first()->name }}</div>
                        {!! Request::get("centre") == ($registration->centre->id) ?
                        null : '<div class="secondary_info">' . $registration->centre->name . '</div>'
                        !!}
                    </td>
                    <td class="center">{{ $registration->getValuation()->getEntitlement() }}</td>
                    <td class="center">{{ $registration->family->rvid }}</td>
                    <td class="right no-wrap">
                        @if( !isset($registration->family->leaving_on) )
                        <a href="{{ route('store.registration.voucher-manager', ['id'=> $registration->id ]) }}"
                            class="link inline-link-button">
                            <div class="link-button">
                                <i class="fa fa-ticket button-icon" aria-hidden="true"></i>Vouchers
                            </div>
                        </a>
                        <a href="{{ route('store.registration.edit', ['id'=> $registration->id ]) }}" class="link
                            inline-link-button">
                            <div class="link-button">
                                <i class="fa fa-pencil button-icon" aria-hidden="true"></i>Edit
                            </div>
                        </a>
                        @else
                        <div class="link-button link-button-small disabled">
                            <i class="fa fa-ticket button-icon" aria-hidden="true"></i>Vouchers
                        </div>
                        <div class="link-button link-button-small disabled">
                            <i class="fa fa-pencil button-icon" aria-hidden="true"></i>Edit
                        </div>
                        @endif
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
    <div>
        {{ $registrations->links() }}
    </div>
</div>
@endsection