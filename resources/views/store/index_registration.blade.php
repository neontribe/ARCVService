@extends('store.layouts.service_master')

@section('title', 'Check / Update Registration')

@section('content')

    @include('store.partials.navbar', ['headerTitle' => 'Search for a family'])
    <div class="content search">
        <div>
            <form action="{{ URL::route('store.registration.index') }}" method="GET" id="searchform">
                {!! csrf_field() !!}
                <div class="search-actions">
                    <input type="search" name="family_name" autocomplete="off" autocorrect="off" spellcheck="false" placeholder="Enter family name">
                    <button>
                        <img src="{{ asset('store/assets/search-light.svg') }}" name="search">
                    </button>
                </div>
            </form>
        </div>
        <div>
            <table>
                <thead>
                    <tr>
                        <td>Name</td>
                        <td class="center">Voucher Entitlement</td>
                        <td class="center">RV-ID</td>
                        <td></td>
                    </tr>
                </thead>
                <tbody>
                @foreach ($registrations as $registration)
                    @if ($registration->family)
                        <tr class="{{ $registration->family->leaving_on ? 'inactive' : 'active' }}">
                            <td class="pri_carer">{{ $registration->family->carers->first()->name }}</td>
                            <td class="center">{{ $registration->family->entitlement }}</td>
                            <td class="center">{{ $registration->family->rvid }}</td>
                            <td class="right no-wrap">
                                @if( !isset($registration->family->leaving_on) )
                                <a href="{{ route("store.registration.voucher-manager", ['id' => $registration->id ]) }}" class="link">
                                    <div class="link-button link-button-small">
                                        <i class="fa fa-ticket button-icon" aria-hidden="true"></i>Vouchers
                                    </div>
                                </a>
                                <a href="{{ route("store.registration.edit", ['id' => $registration->id ]) }}" class="link">
                                    <div class="link-button link-button-small">Edit</div>
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
