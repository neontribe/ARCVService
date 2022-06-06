@extends('store.layouts.printable_master')

@section('title', $sheet_title)

@section('content')

  @include('store.printables.partials.masthead', ['specificPrintNote' => 'Ideally you should print this form every week to keep voucher allocations as up to date as possible.'])

    <div class="content families">
      <h1>Weekly Voucher Collection Sheet</h1>
      <table class="info centre">
        <tr>
          <td>
            <h2>Children's Centre: {{ $centre->name }}</h2>
          </td>
          <td class="week-commencing">
            <p>Week commencing</p>
            <img src="{{ asset('store/assets/date-field.svg') }}">
          </td>
        </tr>
      </table>
      <br/>
      <table class="families_table">
        <tr>
          <th class='hidden_column'>Main carer's name</th>
          <th>RV-ID</th>
          <th class="sml-cell">Weekly voucher allocation</th>
          <th class="sml-cell">Amount of vouchers given out</th>
          <th style="width:20%;color:white;">Voucher Numbers</th>
          <th>Date collected</th>
          <th class="lrg-cell">Initials of person who collected</th>
        </tr>
        @foreach ($registrations as $registration)
        <tr>
          <td class='hidden_column'>
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
        </tr>
      </table>
    </div>


@endsection
<style>
    .hidden_column {
        border:1px solid white;
        border-right: 1px solid #a74e94;
        color:white;
    }
</style>
