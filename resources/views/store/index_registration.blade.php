@extends('store.layouts.service_master')

@section('title', 'Check / Update Registration')

@section('content')

    @include('store.partials.navbar', ['headerTitle' => 'Search for a family'])
    <div class="content search">
        <div class="control-container">
            {{-- Name search --}}
            <div class="search-control">
                <label>Search by name</label>
                <form action="{{ URL::route('store.registration.index') }}" method="GET" id="searchform">
                    {!! csrf_field() !!}
                    <div class="search-actions">
                        <input type="search" name="family_name" autocomplete="off" autocorrect="off" spellcheck="false" placeholder="Enter family name" aria-label="Family Name">
                        <button name="search" aria-label="Search">
                            <img src="{{ asset('store/assets/search-light.svg') }}" alt="search">
                        </button>
                    </div>
                </form>
            </div>
            {{-- Centre filter --}}
            <div class="filter-control">
                <label>Filter by centre</label>
                <form name="centreFilter" id="centre-filter"}}">
                    {!! method_field('put') !!}
                    {!! csrf_field() !!}
                    <select name="centre" onchange="document.centreUserForm.submit()">
                            <option value="all">All</option>
                        @foreach (Auth::user()->centres as $centre)
                            <option
                                    value="{{ $centre->id }}"
                            >{{ $centre->name }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            {{-- Families left checkbox --}}
            <div class="checkbox-control">
                <div class="user-control">
                    <input type="checkbox" class="styled-checkbox" id="families-left" name="families-left" @if( old('consent') ) checked @endif/>
                    <label for="families-left">Show families who have left</label>
                </div>
            </div>
        </div>
        <div>
            <table>
                <thead>
                    <tr>
                        <td>Name<span class="sort-link-container">@include('store.partials.sortableChevron', ['route' => 'store.registration.index', 'orderBy' => 'name', 'direction' => request('direction') ])</span></td>
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
                                <div class="secondary_info">{{ $registration->centre->name }}</div>
                            </td>
                            <td class="center">{{ $registration->getValuation()->getEntitlement() }}</td>
                            <td class="center">{{ $registration->family->rvid }}</td>
                            <td class="right no-wrap">
                                @if( !isset($registration->family->leaving_on) )
                                <a href="{{ route("store.registration.voucher-manager", ['id' => $registration->id ]) }}" class="link">
                                    <div class="link-button link-button-small">
                                        <i class="fa fa-ticket button-icon" aria-hidden="true"></i>Vouchers
                                    </div>
                                </a>
                                <a href="{{ route("store.registration.edit", ['id' => $registration->id ]) }}" class="link">
                                    <div class="link-button link-button-small">
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
