@extends('store.layouts.printable_master')

@section('title', $sheet_title)

@section('content')

    @foreach ( $regs as $index => $reg )
    @include('store.printables.partials.masthead', ['specificPrintNote' => 'Print a new form every 4 weeks so you have the most up to date information available.'])

    <div class="content">
        <h1>{{ $reg["pri_carer"] }}</h1>
        <table>
            <tr class="titles">
                <th colspan="5">
                    <h2>Main Carer's Name:</h2>
                    <p>{{ $reg["pri_carer"] }}</p>
                </th>
                <td rowspan="2" class="colspan">
                    <p>Date Printed:<p>
                    <p> {{ \Carbon\Carbon::now()->toFormattedDateString() }} </p>
                </td>
            </tr>
            <tr class="titles">
                <th colspan="5" >
                    <h3>Children's Centre Name:</h3>
                    <p>{{ $reg["centre"]->name }}</p>
                </th>
            </tr>
            <tr class="titles">
                <td class="med-cell">RV-ID</td>
                <td class="sml-cell">Voucher allocation</td>
                <td class="sml-cell">Vouchers given out</td>
                <td>Voucher numbers</td>
                <td>Date collected</td>
                <td class="lrg-cell">Signature</td>
            </tr>
            <tr>
                <td rowspan="4" class="colspan">{{ $reg["family"]->rvid }}</td>
                <td rowspan="4" class="colspan vouchers"><i class="fa fa-ticket" aria-hidden="true"></i> {{ $reg["family"]->entitlement }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </table>
        <table class="more-info {{ ($index + 1) == count($regs) ? 'no-page-break' : '' }}">
            <tr>
                <td rowspan="2">
                    <p>This family should collect <strong>{{ $reg["family"]->entitlement }}</strong> vouchers per week:</p>
                    <ul>
                        @foreach( $reg["family"]->getCreditReasons() as $credits)
                            <li>
                                <strong>
                                    {{ $credits['reason_vouchers'] }}
                                </strong>
                                {{ str_plural('voucher', $credits['reason_vouchers']) }}
                                because
                                @if ($credits['count'] > 1)
                                    {{ $credits['count'] }}
                                    of the
                                    {{ str_plural($credits['entity'], $credits['count']) }}
                                    are
                                @else
                                    @if ($credits['entity'] == 'family')
                                        the
                                    @else
                                        one
                                    @endif
                                    {{ str_plural($credits['entity'], $credits['count']) }}
                                    is
                                @endif
                                {{ $credits['reason'] }}
                            </li>
                        @endforeach
                    </ul>
                    <p>Their RV-ID is: <strong>{{ $reg["family"]->rvid }}</strong></p>
                </td>
            </tr>
        </table>
    </div>
    @endforeach
@endsection
