@extends('store.layouts.service_master')

@section('title', 'Check / Update Registration')

@section('content')

@include('store.partials.navbar', ['headerTitle' => 'Search for a ' . ($programme ? 'household' : 'family')])
@includeWhen(Session::has('message'), 'store.partials.success')
<div class="content search">
    <div class="control-container">
    </div>
    <div>
        <label><input type="checkbox" id="families_left" class="filter" value="showLeft"/>Show {{ $programme ? 'households' : 'families'}} who have left</label>

        <table id="registrationTable">
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
                        <a href="{{ route('store.registration.voucher-manager', ['registration'=> $registration->id ]) }}"
                            class="link inline-link-button">
                            <div class="link-button">
                                <i class="fa fa-ticket button-icon" aria-hidden="true"></i>Vouchers
                            </div>
                        </a>
                        <a href="{{ route('store.registration.edit', ['registration'=> $registration->id ]) }}" class="link
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
</div>
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
<script>
  $(document).ready(function () {
    var registrationTable = $('#registrationTable').DataTable({
        "columnDefs": [ { "orderable":false, "targets":3 } ],
    });
    $(".inactive").hide(); // Hide families that have left by default.
    $('input.filter').on('change', function() {
        if ($(this).is(":checked") === true) {
            $(".inactive").show();
        } else {
            $(".inactive").hide();
        }
        registrationTable.draw();
    });
  });
</script>
@endsection
