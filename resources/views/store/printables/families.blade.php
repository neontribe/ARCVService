@extends('store.layouts.printable_master')

@section('title', $sheet_title)

@section('content')

  @include('store.printables.partials.masthead', ['specificPrintNote' => 'Ideally you should print this form every week to keep voucher allocations as up to date as possible.'])

    <div class="content families">
      <h1>Weekly Voucher Collection Sheet</h1>
      <table class="info centre">
        <tr>
          <td>
            <h2>Distribution Centre: {{ $centre->name }}</h2>
          </td>
          <td class="week-commencing">
            <p>Week commencing</p>
            <img src="{{ asset('store/assets/date-field.svg') }}">
          </td>
        </tr>
      </table>
      <table class="families_table">
        <tr>
          <th>Main carer's name</th>
          <th>RV-ID</th>
          <th class="sml-cell">Voucher allocation</th>
          <th class="sml-cell">Vouchers given out</th>
          <th>Voucher numbers</th>
          <th>Date collected</th>
          <th class="lrg-cell">Signature</th>
        </tr>
        @foreach ($registrations as $registration)
        <tr>
          <td>
            @if(!empty($registration->getValuation()->getNoticeReasons()))
              <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
            @endif
            {{ $registration->family->pri_carer }}
          </td>
          <td>{{ $registration->family->rvid }}</td>
          <td><i class="fa fa-ticket" aria-hidden="true"></i> {{ $registration->getValuation()->getEntitlement() }}</td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
        @endforeach
      </table>
      <table class="info general">
        <tr>
          <td valign="top">
            <h3><i class="fa fa-question-circle" aria-hidden="true"></i> Hints &amp; Tips</h3>
            <p>When did you last chat to them about how they're finding shopping at the market?</p>
          </td>
          <td valign="top">
            <h3><i class="fa fa-exclamation-circle" aria-hidden="true"></i> Attention</h3>
            <p>When this icon is displayed for a family, next month the number of vouchers the family can collect will change because of a child's birthday. Please help them to get ready for this. You can find more information about the change if you search for the family in the Rose Voucher app.</p>
          </td>
        </tr>
      </table>
    </div>


@endsection
